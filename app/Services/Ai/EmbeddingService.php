<?php

namespace App\Services\Ai;

use App\Models\BotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Standalone Gemini embedding client — deliberately NOT routed through AiRouter.
 * Embeddings use a different endpoint shape than chat completions, and a failure here
 * should degrade callers to their static fallback rather than burning AiRouter's
 * retry/circuit-breaker budget meant for customer-facing chat replies.
 */
class EmbeddingService
{
    private const MODEL = 'text-embedding-004';

    public function embed(string $text): ?array
    {
        $apiKey = BotConfig::get('gemini_api_key') ?: (config('services.gemini.key') ?? '');
        if ($apiKey === '') {
            return null;
        }

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':embedContent';

        try {
            $response = Http::timeout(10)->post("{$endpoint}?key={$apiKey}", [
                'model'   => 'models/' . self::MODEL,
                'content' => ['parts' => [['text' => $text]]],
            ]);

            if ($response->failed()) {
                Log::warning('Gemini embedding request failed', [
                    'status' => $response->status(),
                    'error'  => $response->json('error.message') ?? 'Unknown error',
                ]);

                return null;
            }

            $values = $response->json('embedding.values');

            return is_array($values) && !empty($values) ? $values : null;
        } catch (\Exception $e) {
            Log::warning('Gemini embedding request errored', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
