<?php

namespace App\Jobs;

use App\Models\BotConfig;
use App\Models\ConversationAnalysis;
use App\Models\WhatsappLog;
use App\Services\GroqService;
use App\Services\GeminiService;
use App\Services\OpenRouterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KnowledgeSynthesizerJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 180;

    public function handle(GroqService $groq, GeminiService $gemini, OpenRouterService $openrouter): void
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
        $transcripts = [];

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

            $transcripts[] = $pairs;
        }

        if (empty($transcripts)) {
            Log::info('KnowledgeSynthesizerJob: no transcript data available');
            return;
        }

        $combined = implode("\n\n===\n\n", array_slice($transcripts, 0, 10));

        $prompt = <<<PROMPT
Dari percakapan WhatsApp bot berikut antara calon peserta dan asisten Languages by Fara, ekstrak 5-8 Q&A terbaik yang menunjukkan jawaban bot yang akurat dan membantu.

Format output: JSON array. Setiap item: {"q": "pertanyaan singkat", "a": "jawaban singkat maks 150 karakter"}
Hanya kembalikan JSON valid, tanpa teks lain.

PERCAKAPAN:
{$combined}
PROMPT;

        $systemPrompt = 'Kamu adalah ekstractor knowledge base. Output HANYA JSON valid, tidak ada penjelasan.';

        $services = [
            'groq'       => $groq,
            'gemini'     => $gemini,
            'openrouter' => $openrouter,
        ];

        $orderStr = BotConfig::get('ai_provider_order', 'groq,gemini,openrouter');
        $order    = array_filter(array_map('trim', explode(',', $orderStr)));

        $rawJson = null;
        foreach ($order as $provider) {
            $service = $services[$provider] ?? null;
            if (!$service) {
                continue;
            }
            $result = $service->chat($prompt, $systemPrompt, 800, 0.3, []);
            if ($result['success']) {
                $rawJson = $result['reply'];
                break;
            }
        }

        if (!$rawJson) {
            Log::warning('KnowledgeSynthesizerJob: all AI providers failed');
            return;
        }

        // Strip markdown code fences if present
        $rawJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
        $rawJson = preg_replace('/\s*```$/', '', $rawJson);

        $items = json_decode(trim($rawJson), true);

        if (!is_array($items) || empty($items)) {
            Log::warning('KnowledgeSynthesizerJob: invalid JSON response', ['raw' => substr($rawJson, 0, 200)]);
            return;
        }

        // Build compact knowledge string
        $snippets = array_map(fn($i) => 'Q: ' . ($i['q'] ?? '') . ' → ' . ($i['a'] ?? ''), $items);
        $knowledge = implode("\n", $snippets);

        BotConfig::updateOrCreate(
            ['key' => 'dynamic_knowledge'],
            ['key' => 'dynamic_knowledge', 'value' => mb_substr($knowledge, 0, 2000), 'type' => 'textarea', 'label' => 'Dynamic Knowledge (Auto-generated)', 'group' => 'ai']
        );

        Cache::forget('bot_config_dynamic_knowledge');

        // Trigger digest rebuild so dynamic_knowledge is picked up
        BuildFaqDigestJob::dispatch();

        Log::info('KnowledgeSynthesizerJob: done', ['snippets' => count($items)]);
    }
}
