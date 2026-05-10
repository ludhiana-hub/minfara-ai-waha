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
     * @return array{success: bool, reply: string, tokens: int|null, error: string|null}
     */
    public function chat(string $userMessage, string $systemPrompt, int $maxTokens = 500, float $temperature = 0.7): array
    {
        try {
            $response = Http::timeout(15)
                ->post("{$this->endpoint}?key={$this->apiKey}", [
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => [
                        [
                            'role'  => 'user',
                            'parts' => [['text' => $userMessage]],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => $maxTokens,
                        'temperature'     => $temperature,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API failed', [
                    'status' => $response->status(),
                    'error'  => $response->json('error.message') ?? 'Unknown error',
                ]);

                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $response->json('error.message') ?? 'Gemini API error'];
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
