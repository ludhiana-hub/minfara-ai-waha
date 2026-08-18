<?php

namespace App\Jobs;

use App\Models\AnalyticsDailySummary;
use App\Models\ConversationAnalysis;
use App\Models\FaqSuggestion;
use App\Models\KnowledgeSuggestion;
use App\Models\TrainingMaterial;
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

        // Sesi sukses dengan niat beli — bahan ekstraksi Q&A pengetahuan.
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

        // Sumber tambahan — contoh bahasa admin asli, gap FAQ, konteks agregat hari itu, dan
        // materi yang di-upload admin (export chat manual, riset kompetitor, teknik sales).
        $humanExamples  = $this->buildHumanExamples($since);
        $faqGaps        = $this->buildFaqGaps();
        $dailyContext   = $this->buildDailyContext($since);
        $trainingNotes  = $this->buildTrainingMaterials();

        if (
            empty($knowledgeTranscripts) && empty($coachingTranscripts)
            && $humanExamples === '' && $faqGaps === '' && $dailyContext === '' && $trainingNotes === ''
        ) {
            Log::info('KnowledgeSynthesizerJob: no qualifying data found for any source', ['date' => $since]);
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
        if ($humanExamples !== '') {
            $sections[] = "CONTOH BAHASA ADMIN ASLI (manusia, bukan AI, dari sesi human takeover kemarin):\n" . $humanExamples;
        }
        if ($faqGaps !== '') {
            $sections[] = "PERTANYAAN YANG SERING MUNCUL TAPI BELUM TERJAWAB DI FAQ:\n" . $faqGaps;
        }
        if ($dailyContext !== '') {
            $sections[] = "KONTEKS AGREGAT HARI ITU:\n" . $dailyContext;
        }
        if ($trainingNotes !== '') {
            $sections[] = "MATERI TAMBAHAN (upload admin — export chat manual, riset kompetitor, teknik sales):\n" . $trainingNotes;
        }
        $combined = implode("\n\n---\n\n", $sections);

        $prompt = <<<PROMPT
Kamu membantu tim Languages by Fara melatih asisten WhatsApp AI mereka (MinFara AI) supaya makin efektif sebagai sales consultant — bukan cuma FAQ bot. Analisis SEMUA data di bawah (bisa lebih dari satu sumber sekaligus) dan hasilkan dua jenis output. Cari ROOT CAUSE di balik tiap temuan (kenapa closing gagal, kenapa customer ragu, kenapa jawaban kurang efektif) — jangan cuma observasi permukaan.

1. "knowledge_items" — dari bagian PERCAKAPAN SUKSES, PERTANYAAN BELUM TERJAWAB, dan MATERI TAMBAHAN (kalau ada): ekstrak 5-8 Q&A terbaik. Jawaban harus lengkap & jelas (boleh lebih dari satu kalimat, jelaskan manfaat nyata, bukan cuma fakta mentah), maks ~400 karakter. Format tiap item: {"q": "pertanyaan singkat", "a": "jawaban lengkap & jelas"}. Kalau tidak ada bahan yang relevan, kembalikan array kosong.

2. "coaching_items" — dari bagian PERCAKAPAN PERLU DITINGKATKAN, CONTOH BAHASA ADMIN ASLI, KONTEKS AGREGAT, dan MATERI TAMBAHAN (kalau ada): temukan 3-6 peluang perbaikan gaya/teknik bot, dinilai dari 3 sudut pandang:
   - Peluang closing yang terlewat (bot tidak mengarahkan ke checkout https://mitfara.com padahal momennya pas)
   - Product knowledge yang kurang dieksplorasi padahal relevan dengan pertanyaan customer
   - Gaya bahasa yang terasa kaku/template/robotic — bandingkan dengan CONTOH BAHASA ADMIN ASLI kalau tersedia, beri versi lebih natural & manusiawi
   Format tiap item: {"finding": "observasi + root cause singkat", "recommendation": "rekomendasi teknik konkret", "example_rewrite": "contoh kalimat balasan WA siap pakai yang menerapkan rekomendasi"}.
   PENTING soal example_rewrite: HARUS tetap ringkas & natural SESUAI KEBUTUHAN KONTEKS — jangan dibuat panjang kalau pertanyaan aslinya bisa dijawab singkat. ini tentang KUALITAS teknik, bukan soal membuat balasan jadi panjang.
   PENTING soal fakta: rekomendasi HANYA soal cara penyampaian/teknik, DILARANG mengarang fakta produk baru atau urgency/diskon palsu yang tidak ada di data. Kalau tidak ada bahan yang relevan, kembalikan array kosong.

Format output: JSON object dengan dua key "knowledge_items" dan "coaching_items", masing-masing array (boleh kosong []).
Hanya kembalikan JSON valid, tanpa teks lain.

{$combined}
PROMPT;

        $systemPrompt = 'Kamu adalah AI sales coach & knowledge extractor yang tajam dan berbasis data. Output HANYA JSON valid, tidak ada penjelasan.';

        $result = $router->run(
            AiRequest::make('synthesis', $prompt)
                ->withSystem($systemPrompt)
                ->withMaxTokens(2500)
                ->withTemperature(0.3)
                ->expectingJson()
        );

        if (!$result->success) {
            Log::warning('KnowledgeSynthesizerJob: all AI providers failed', ['attempt_log' => $result->attemptLog]);
            return;
        }

        $contributingPhones = array_values(array_unique(array_merge($knowledgePhones, $coachingPhones)));

        $createdKnowledge = $this->storeKnowledgeItems($result->json['knowledge_items'] ?? [], $knowledgePhones ?: $contributingPhones, $since);
        $createdCoaching  = $this->storeCoachingItems($result->json['coaching_items'] ?? [], $coachingPhones ?: $contributingPhones, $since);

        Log::info('KnowledgeSynthesizerJob: done', [
            'date'      => $since,
            'knowledge' => $createdKnowledge,
            'coaching'  => $createdCoaching,
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

    /**
     * Balasan admin sendiri saat human takeover — WhatsAppController::handleOwnerReply()
     * menyimpan teks yang diketik admin ke kolom message_in (message_out selalu null di sini),
     * jadi ini murni contoh bahasa manusia asli, bukan hasil AI.
     */
    private function buildHumanExamples(string $since): string
    {
        $examples = WhatsappLog::where('mode', 'human_takeover')
            ->whereBetween('responded_at', [$since . ' 00:00:00', $since . ' 23:59:59'])
            ->whereNotNull('message_in')
            ->limit(15)
            ->pluck('message_in')
            ->map(fn($m) => '- ' . mb_substr($m, 0, 200))
            ->implode("\n");

        return $examples;
    }

    private function buildFaqGaps(): string
    {
        return FaqSuggestion::where('status', 'pending')
            ->orderByDesc('frequency')
            ->limit(10)
            ->pluck('question')
            ->map(fn($q) => '- ' . $q)
            ->implode("\n");
    }

    private function buildDailyContext(string $since): string
    {
        $summary = AnalyticsDailySummary::where('summary_date', $since)->first();
        if (!$summary) {
            return '';
        }

        $lines = [
            'Total sesi: ' . $summary->total_sessions . ' | Pelanggan unik: ' . $summary->unique_customers,
            'Sentiment: positif ' . $summary->sentiment_positive . ', netral ' . $summary->sentiment_neutral . ', negatif ' . $summary->sentiment_negative,
            'Rata-rata purchase intent: ' . $summary->avg_intent_score . '/10',
        ];
        if (!empty($summary->top_topics)) {
            $lines[] = 'Topik teratas: ' . implode(', ', array_slice((array) $summary->top_topics, 0, 5));
        }
        if (!empty($summary->customer_segments)) {
            $lines[] = 'Segment customer: ' . json_encode($summary->customer_segments);
        }

        return implode("\n", $lines);
    }

    private function buildTrainingMaterials(): string
    {
        $materials = TrainingMaterial::active()->latest()->get(['title', 'category', 'content']);
        if ($materials->isEmpty()) {
            return '';
        }

        $totalCap = 3000;
        $lines    = [];
        $len      = 0;

        foreach ($materials as $m) {
            $snippet = "[{$m->category}] {$m->title}: " . mb_substr($m->content, 0, 800);
            if ($len + mb_strlen($snippet) > $totalCap) {
                break;
            }
            $lines[] = $snippet;
            $len    += mb_strlen($snippet);
        }

        return implode("\n\n", $lines);
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

            $question = mb_substr($q, 0, 900);
            if ($this->suggestionAlreadyExists('knowledge', $question)) {
                continue;
            }

            KnowledgeSuggestion::create([
                'question'       => $question,
                'answer'         => mb_substr($a, 0, 900),
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
            $exampleRewrite = trim((string) ($item['example_rewrite'] ?? ''));
            if ($finding === '' || $recommendation === '') {
                continue;
            }

            $answer = 'Rekomendasi: ' . $recommendation;
            if ($exampleRewrite !== '') {
                $answer .= "\n\nContoh kalimat: " . $exampleRewrite;
            }

            $question = mb_substr($finding, 0, 900);
            if ($this->suggestionAlreadyExists('coaching', $question)) {
                continue;
            }

            KnowledgeSuggestion::create([
                'question'       => $question,
                'answer'         => mb_substr($answer, 0, 900),
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

    // Same topic flagged on consecutive days would otherwise pile up as separate 'pending'
    // rows forever — an exact-match check on the still-actionable statuses (pending/approved)
    // is cheap and catches the common case without needing fuzzy matching.
    private function suggestionAlreadyExists(string $type, string $question): bool
    {
        return KnowledgeSuggestion::where('type', $type)
            ->where('question', $question)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
    }
}
