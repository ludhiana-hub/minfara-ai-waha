<?php

namespace App\Services\Ai;

use App\Models\BotConfig;
use Illuminate\Support\Facades\Cache;
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
    // text-embedding-004 was shut down by Google (404 "not found for API version v1beta").
    public const MODEL = 'gemini-embedding-001';

    // gemini-embedding-001 defaults to 3072 dims; 768 keeps the knowledge_chunks table and the
    // cosine loop small with negligible quality loss for a few hundred short FAQ chunks.
    private const DIMENSIONS = 768;

    // After a failure, skip the endpoint for a while. Without this every customer message pays a
    // doomed Gemini request (RPM + latency) before falling back to the FAQ digest.
    private const BACKOFF_KEY     = 'embedding_backoff';
    private const BACKOFF_SECONDS = 300;

    public function embed(string $text): ?array
    {
        $apiKey = BotConfig::get('gemini_api_key') ?: (config('services.gemini.key') ?? '');
        if ($apiKey === '') {
            return null;
        }

        if (Cache::has(self::BACKOFF_KEY)) {
            return null;
        }

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':embedContent';

        try {
            // Key in a header, not the URL — same reason as GeminiProvider: URLs end up in
            // request logs and Telescope.
            $response = Http::timeout(10)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post($endpoint, [
                    'model'                => 'models/' . self::MODEL,
                    'content'              => ['parts' => [['text' => $text]]],
                    'outputDimensionality' => self::DIMENSIONS,
                ]);

            if ($response->failed()) {
                Cache::put(self::BACKOFF_KEY, true, self::BACKOFF_SECONDS);
                Log::warning('Gemini embedding request failed', [
                    'status' => $response->status(),
                    'error'  => $response->json('error.message') ?? 'Unknown error',
                ]);

                return null;
            }

            $values = $response->json('embedding.values');

            return is_array($values) && !empty($values) ? $values : null;
        } catch (\Exception $e) {
            Cache::put(self::BACKOFF_KEY, true, self::BACKOFF_SECONDS);
            Log::warning('Gemini embedding request errored', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
