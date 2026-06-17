<?php

namespace App\Services;

use App\Services\Contracts\AiServiceInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class OpenAiCompatibleService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $endpoint;
    protected string $providerName;
    protected array  $extraPayload = [];

    public function withModel(string $model): static
    {
        $clone        = clone $this;
        $clone->model = $model;
        return $clone;
    }

    protected function httpClient(): PendingRequest
    {
        return Http::timeout(20)->withToken($this->apiKey);
    }

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

        $payload = array_merge([
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temperature,
        ], $this->extraPayload);

        try {
            $response = $this->httpClient()->post($this->endpoint, $payload);

            if ($response->status() === 429) {
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'quota_exceeded'];
            }

            if ($response->failed()) {
                $status  = $response->status();
                $message = $response->json('error.message') ?? 'Unknown error';

                Log::error("{$this->providerName} API failed", ['status' => $status, 'error' => $message]);

                return ['success' => false, 'reply' => '', 'tokens' => null,
                        'error'   => $status === 429 ? 'quota_exceeded' : $message];
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                Log::warning("{$this->providerName} response missing expected structure");
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Invalid response format'];
            }

            $text   = trim($data['choices'][0]['message']['content']);
            // Strip <think>...</think> blocks (Qwen3 and other reasoning models)
            $text   = trim(preg_replace('/<think>.*?<\/think>/si', '', $text));
            $tokens = $data['usage']['total_tokens'] ?? null;

            if (empty($text)) {
                return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Empty response'];
            }

            return ['success' => true, 'reply' => $text, 'tokens' => $tokens, 'error' => null];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("{$this->providerName} connection timeout", ['message' => $e->getMessage()]);
            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => 'Connection timeout'];
        } catch (\Exception $e) {
            Log::error("{$this->providerName} unexpected error", ['message' => $e->getMessage()]);
            return ['success' => false, 'reply' => '', 'tokens' => null, 'error' => $e->getMessage()];
        }
    }
}
