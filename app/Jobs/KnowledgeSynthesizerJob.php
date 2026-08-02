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

class KnowledgeSynthesizerJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 180;

    public function handle(AiRouter $router): void
    {
        $since = now()->subDays(7)->toDateString();

        // Only successful sessions with buying intent
        $sessions = ConversationAnalysis::where('session_date', '>=', $since)
            ->where('resolved', true)
            ->where('purchase_intent_score', '>=', 6)
            ->whereNotNull('phone_number')
            ->pluck('phone_number')
            ->unique();

        if ($sessions->isEmpty()) {
            Log::info('KnowledgeSynthesizerJob: no qualifying sessions found');
            return;
        }

        // Collect up to 20 sessions, build transcript snippets
        $transcripts         = [];
        $contributingPhones  = [];

        foreach ($sessions->take(20) as $phone) {
            $logs = WhatsappLog::where('from_number', $phone)
                ->where('responded_at', '>=', now()->subDays(7))
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
        }

        if (empty($transcripts)) {
            Log::info('KnowledgeSynthesizerJob: no transcript data available');
            return;
        }

        $combined   = implode("\n\n===\n\n", array_slice($transcripts, 0, 10));
        $usedPhones = array_slice($contributingPhones, 0, 10);

        // Object envelope ({"items": [...]}) rather than a bare top-level array — OpenAI-
        // compatible `response_format: json_object` mode (used via ->expectingJson() below)
        // requires the root to be a JSON object, and a bare array would either be rejected
        // or silently coerced by some providers.
        $prompt = <<<PROMPT
Dari percakapan WhatsApp bot berikut antara calon peserta dan asisten Languages by Fara, ekstrak 5-8 Q&A terbaik yang menunjukkan jawaban bot yang akurat dan membantu.

Format output: JSON object dengan satu key "items", berisi array. Setiap item: {"q": "pertanyaan singkat", "a": "jawaban singkat maks 150 karakter"}
Hanya kembalikan JSON valid, tanpa teks lain.

PERCAKAPAN:
{$combined}
PROMPT;

        $systemPrompt = 'Kamu adalah ekstractor knowledge base. Output HANYA JSON valid, tidak ada penjelasan.';

        $result = $router->run(
            AiRequest::make('synthesis', $prompt)
                ->withSystem($systemPrompt)
                ->withMaxTokens(800)
                ->withTemperature(0.3)
                ->expectingJson()
        );

        if (!$result->success) {
            Log::warning('KnowledgeSynthesizerJob: all AI providers failed', ['attempt_log' => $result->attemptLog]);
            return;
        }

        $items = $result->json['items'] ?? null;

        if (!is_array($items) || empty($items)) {
            Log::warning('KnowledgeSynthesizerJob: response missing usable "items" array', ['json' => $result->json]);
            return;
        }

        // Simpan sebagai saran pending — TIDAK langsung ditulis ke dynamic_knowledge/prompt live.
        // Admin harus approve tiap item lewat CMS (KnowledgeSuggestionController) agar hasil ekstraksi
        // AI (yang bisa saja berisi halusinasi dari percakapan sebelumnya) tidak otomatis tayang ke
        // semua customer tanpa direview.
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
                'example_phones' => $usedPhones,
                'period_start'   => $since,
                'period_end'     => now()->toDateString(),
                'status'         => 'pending',
            ]);
            $created++;
        }

        Log::info('KnowledgeSynthesizerJob: done', ['snippets' => $created]);
    }
}
