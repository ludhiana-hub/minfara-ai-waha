<?php

namespace App\Services;

use App\Models\BotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey   = BotConfig::get('gemini_api_key') ?: config('services.gemini.key', '');
        $this->model    = BotConfig::get('gemini_model')   ?: config('services.gemini.model', 'gemini-2.0-flash');
        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
Ce     * @param  array<array{role: string, content: string}> $history  Neutral format [{role: user/assistant, content: string}]
     * @return array{success: bool, reply: string, tokens: int|null, error: string|null}
     */
    public function chat(string $userMessage, string $systemPrompt, int $maxTokens = 500, float $temperature = 0.7, array $history = []): array
    {
        // Convert neutral format to Gemini format (user/assistant → user/model, content → parts)
        $contents = array_map(fn($turn) => [
            'role'  => $turn['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $turn['content']]],
        ], $history);

        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents'           => $contents,
            'generationConfig'   => [
                'maxOutputTokens' => $maxTokens,
                'temperature'     => $temperature,
            ],
        ];

        try {
            $response = Http::timeout(20)
                ->post("{$this->endpoint}?key={$this->apiKey}", $payload);

            // Retry once after 3s on rate-limit (429) — handles per-minute quota bursts
            if ($response->status() === 429) {
                sleep(3);
                $response = Http::timeout(20)
                    ->post("{$this->endpoint}?key={$this->apiKey}", $payload);
            }

            if ($response->failed()) {
                $status  = $response->status();
                $message = $response->json('error.message') ?? 'Unknown error';

                Log::error('Gemini API failed', ['status' => $status, 'error' => $message]);

                $userFacing = $status === 429
                    ? 'quota_exceeded'
                    : $message;

                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $userFacing];
            }

            $data = $response->json();

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                Log::warning('Gemini response missing expected structure');
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Invalid response format'];
            }

            $text   = trim($data['candidates'][0]['content']['parts'][0]['text']);
            $tokens = $data['usageMetadata']['totalTokenCount'] ?? null;

            if (empty($text)) {
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Empty response'];
            }

            return ['success' => true, 'reply' => $text, 'tokens' => $tokens, 'error' => null];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini connection timeout', ['message' => $e->getMessage()]);
            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Connection timeout'];
        } catch (\Exception $e) {
            Log::error('Gemini unexpected error', ['message' => $e->getMessage()]);
            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $e->getMessage()];
        }
    }
}
