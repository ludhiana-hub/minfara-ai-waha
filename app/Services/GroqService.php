<?php

namespace App\Services;

use App\Models\BotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private string $apiKey;
    private string $model;
    private string $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = BotConfig::get('groq_api_key') ?: config('services.groq.key', '');
        $this->model  = BotConfig::get('groq_model')   ?: config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    /**
     * @param  array<array{role: string, content: string}> $history  Neutral format [{role: user/assistant, content: string}]
     * @return array{success: bool, reply: string, tokens: int|null, error: string|null}
     */
    public function chat(string $userMessage, string $systemPrompt, int $maxTokens = 500, float $temperature = 0.7, array $history = []): array
    {
        $messages = [];

        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temperature,
        ];

        try {
            $response = Http::timeout(20)
                ->withToken($this->apiKey)
                ->post($this->endpoint, $payload);

            // Retry once after 3s on rate-limit
            if ($response->status() === 429) {
                sleep(3);
                $response = Http::timeout(20)
                    ->withToken($this->apiKey)
                    ->post($this->endpoint, $payload);
            }

            if ($response->failed()) {
                $status  = $response->status();
                $message = $response->json('error.message') ?? 'Unknown error';

                Log::error('Groq API failed', ['status' => $status, 'error' => $message]);

                $userFacing = $status === 429 ? 'quota_exceeded' : $message;
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $userFacing];
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                Log::warning('Groq response missing expected structure');
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Invalid response format'];
            }

            $text   = trim($data['choices'][0]['message']['content']);
            $tokens = $data['usage']['total_tokens'] ?? null;

            if (empty($text)) {
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Empty response'];
            }

            return ['success' => true, 'reply' => $text, 'tokens' => $tokens, 'error' => null];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Groq connection timeout', ['message' => $e->getMessage()]);
            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Connection timeout'];
        } catch (\Exception $e) {
            Log::error('Groq unexpected error', ['message' => $e->getMessage()]);
            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $e->getMessage()];
        }
    }
}
