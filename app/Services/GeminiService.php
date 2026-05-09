<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey   = config('services.gemini.key');
        $this->model    = config('services.gemini.model', 'gemini-2.0-flash');
        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * @return array{success: bool, reply: string, tokens: int|null, error: string|null}
     */
    public function chat(string $userMessage, string $systemPrompt): array
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
                        'maxOutputTokens' => 500,
                        'temperature'     => 0.7,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API failed', [
                    'status' => $response->status(),
                    'error'  => $response->json('error.message') ?? 'Unknown error',
                    // Jangan log full body karena bisa contain sensitive data
                ]);

                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $response->json('error.message') ?? 'Gemini API error'];
            }

            $data   = $response->json();
            
            // Validate response structure
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                Log::warning('Gemini response missing expected structure', [
                    'response' => array_keys($data),
                ]);
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Invalid response format'];
            }
            
            $text   = trim($data['candidates'][0]['content']['parts'][0]['text']);
            $tokens = $data['usageMetadata']['totalTokenCount'] ?? null;

            // Validate reply is not empty
            if (empty($text)) {
                Log::warning('Gemini returned empty reply');
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Empty response'];
            }

            return ['success' => true, 'reply' => $text, 'tokens' => $tokens, 'error' => null];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini connection timeout', ['message' => $e->getMessage()]);

            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Connection timeout'];

        } catch (\Exception $e) {
            Log::error('Gemini unexpected error', ['message' => $e->getMessage(), 'class' => get_class($e)]);

            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $e->getMessage()];
        }
    }
}
