<?php

namespace App\Jobs;

use App\Models\ConversationAnalysis;
use App\Models\KnowledgeSuggestion;
use App\Models\WhatsappLog;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiRouter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class KnowledgeSynthesizerJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 180;

    public function handle(AiRouter $router): void
    {
        $since = now()->subDay()->toDateString();

        // Sesi sukses dengan niat beli — bahan ekstraksi Q&A pengetahuan (seperti sebelumnya,
        // tapi sekarang dibatasi ke tanggal kemarin saja, bukan rolling 7-hari, supaya job ini
        // bisa jalan harian tanpa memproses ulang percakapan yang sama berkali-kali).
        $knowledgeSessions = ConversationAnalysis::where('session_date', $since)
            ->where('resolved', true)
            ->where('purchase_intent_score', '>=', 6)
            ->whereNotNull('phone_number')
            ->pluck('phone_number')
            ->unique();

        // Sesi objector/minat rendah kemarin — bahan cari peluang coaching (closing terlewat,
        // product knowledge kurang tergali, gaya bahasa kaku).
        $coachingSessions = ConversationAnalysis::where('session_date', $since)
            ->where(function ($q) {
                $q->whereIn('customer_segment', ['objector', 'window_shopper', 'churner_signal'])
                    ->orWhere('purchase_intent_score', '<=', 4);
            })
            ->whereNotNull('phone_number')
            ->pluck('phone_number')
            ->unique();

        [$knowledgeTranscripts, $knowledgePhones] = $this->buildTranscripts($knowledgeSessions, $since);
        [$coachingTranscripts, $coachingPhones]   = $this->buildTranscripts($coachingSessions, $since);

        if (empty($knowledgeTranscripts) && empty($coachingTranscripts)) {
            Log::info('KnowledgeSynthesizerJob: no qualifying sessions/transcripts found', ['date' => $since]);
            return;
        }

        $sections = [];
        if (!empty($knowledgeTranscripts)) {
            $sections[] = "PERCAKAPAN SUKSES (untuk ekstraksi pengetahuan):\n"
                . implode("\n\n===\n\n", $knowledgeTranscripts);
        }
        if (!empty($coachingTranscripts)) {
            $sections[] = "PERCAKAPAN PERLU DITINGKATKAN (untuk coaching sales):\n"
                . implode("\n\n===\n\n", $coachingTranscripts);
        }
        $combined = implode("\n\n---\n\n", $sections);

        $prompt = <<<PROMPT
Kamu membantu tim Languages by Fara melatih asisten WhatsApp AI mereka (MinFara AI) supaya makin efektif sebagai sales consultant — bukan cuma FAQ bot. Analisis percakapan di bawah dan hasilkan dua jenis output:

1. "knowledge_items" — dari bagian PERCAKAPAN SUKSES (kalau ada): ekstrak 5-8 Q&A terbaik yang menunjukkan jawaban bot yang akurat dan membantu. Format tiap item: {"q": "pertanyaan singkat", "a": "jawaban singkat maks 150 karakter"}. Kalau tidak ada bagian PERCAKAPAN SUKSES, kembalikan array kosong.

2. "coaching_items" — dari bagian PERCAKAPAN PERLU DITINGKATKAN (kalau ada): temukan 3-6 peluang perbaikan gaya/teknik bot, dinilai dari 3 sudut pandang:
   - Peluang closing yang terlewat (bot tidak mengarahkan ke checkout https://mitfara.com padahal momennya pas)
   - Product knowledge yang kurang dieksplorasi padahal relevan dengan pertanyaan customer
   - Gaya bahasa yang terasa kaku/template/robotic — beri versi lebih natural & manusiawi
   Format tiap item: {"finding": "observasi singkat apa yang kurang optimal", "recommendation": "rekomendasi kalimat/teknik konkret yang lebih baik, maks 250 karakter"}.
   PENTING: rekomendasi HANYA soal cara penyampaian (teknik/gaya), DILARANG mengarang fakta produk baru atau urgency/diskon palsu yang tidak ada di percakapan. Kalau tidak ada bagian PERCAKAPAN PERLU DITINGKATKAN, kembalikan array kosong.

Format output: JSON object dengan dua key "knowledge_items" dan "coaching_items", masing-masing array (boleh kosong []).
Hanya kembalikan JSON valid, tanpa teks lain.

{$combined}
PROMPT;

        $systemPrompt = 'Kamu adalah AI sales coach & knowledge extractor. Output HANYA JSON valid, tidak ada penjelasan.';

        $result = $router->run(
            AiRequest::make('synthesis', $prompt)
                ->withSystem($systemPrompt)
                ->withMaxTokens(1200)
                ->withTemperature(0.3)
                ->expectingJson()
        );

        if (!$result->success) {
            Log::warning('KnowledgeSynthesizerJob: all AI providers failed', ['attempt_log' => $result->attemptLog]);
            return;
        }

        $createdKnowledge = $this->storeKnowledgeItems($result->json['knowledge_items'] ?? [], $knowledgePhones, $since);
        $createdCoaching  = $this->storeCoachingItems($result->json['coaching_items'] ?? [], $coachingPhones, $since);

        Log::info('KnowledgeSynthesizerJob: done', [
            'date'     => $since,
            'knowledge'=> $createdKnowledge,
            'coaching' => $createdCoaching,
        ]);
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>} [transcripts, contributingPhones]
     */
    private function buildTranscripts(Collection $phones, string $since): array
    {
        $transcripts = [];
        $contributingPhones = [];

        foreach ($phones->take(20) as $phone) {
            $logs = WhatsappLog::where('from_number', $phone)
                ->whereBetween('responded_at', [$since . ' 00:00:00', $since . ' 23:59:59'])
                ->whereNotNull('message_in')
                ->whereNotNull('message_out')
                ->whereIn('mode', ['ai', 'faq'])
                ->orderBy('responded_at')
                ->limit(6)
                ->get(['message_in', 'message_out']);

            if ($logs->isEmpty()) {
                continue;
            }

            $pairs = $logs->map(fn($l) =>
                'Q: ' . mb_substr($l->message_in, 0, 150) . "\nA: " . mb_substr($l->message_out, 0, 200)
            )->implode("\n---\n");

            $transcripts[]        = $pairs;
            $contributingPhones[] = $phone;

            if (count($transcripts) >= 10) {
                break;
            }
        }

        return [$transcripts, $contributingPhones];
    }

    private function storeKnowledgeItems(mixed $items, array $usedPhones, string $since): int
    {
        if (!is_array($items) || empty($items)) {
            return 0;
        }

        $created = 0;
        foreach ($items as $item) {
            $q = trim((string) ($item['q'] ?? ''));
            $a = trim((string) ($item['a'] ?? ''));
            if ($q === '' || $a === '') {
                continue;
            }

            // Jawaban yang mengarahkan ke admin (wa.me/nomor WA) adalah fallback situasional,
            // bukan pengetahuan reusable — kalau lolos jadi dynamic_knowledge permanen, bot jadi
            // makin sering nyaranin hubungi admin alih-alih checkout mandiri di website.
            if (preg_match('/wa\.me|hubungi admin|admin kami/i', $a)) {
                continue;
            }

            KnowledgeSuggestion::create([
                'question'       => mb_substr($q, 0, 500),
                'answer'         => mb_substr($a, 0, 500),
                'type'           => 'knowledge',
                'example_phones' => $usedPhones,
                'period_start'   => $since,
                'period_end'     => $since,
                'status'         => 'pending',
            ]);
            $created++;
        }

        return $created;
    }

    private function storeCoachingItems(mixed $items, array $usedPhones, string $since): int
    {
        if (!is_array($items) || empty($items)) {
            return 0;
        }

        $created = 0;
        foreach ($items as $item) {
            $finding        = trim((string) ($item['finding'] ?? ''));
            $recommendation = trim((string) ($item['recommendation'] ?? ''));
            if ($finding === '' || $recommendation === '') {
                continue;
            }

            KnowledgeSuggestion::create([
                'question'       => mb_substr($finding, 0, 500),
                'answer'         => mb_substr($recommendation, 0, 500),
                'type'           => 'coaching',
                'example_phones' => $usedPhones,
                'period_start'   => $since,
                'period_end'     => $since,
                'status'         => 'pending',
            ]);
            $created++;
        }

        return $created;
    }
}
