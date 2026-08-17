<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiCall;
use App\Services\Ai\AiRawResult;
use App\Services\Ai\Capability;
use App\Services\Ai\Contracts\AiProviderContract;
use App\Services\Ai\Support\ErrorNormalizer;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wire-format base for every OpenAI-compatible provider (Groq, OpenRouter, NVIDIA). The
 * payload/parse/error-handling logic here is a straight lift from the old
 * OpenAiCompatibleService::chat() — kept behaviorally identical so switching a consumer
 * over to AiRouter never changes what goes out on the wire.
 */
abstract class OpenAiCompatibleProvider implements AiProviderContract
{
    abstract protected function endpoint(): string;

    abstract protected function apiKey(): string;

    protected function extraPayload(string $model): array
    {
        return [];
    }

    protected function httpClient(int $timeoutSeconds): PendingRequest
    {
        return Http::timeout($timeoutSeconds)->withToken($this->apiKey());
    }

    public function hasKey(): bool
    {
        return !empty($this->apiKey());
    }

    public function supports(Capability $capability, string $model): bool
    {
        return match ($capability) {
            Capability::JsonMode  => true,
            Capability::Tools     => false, // wired in Fase 3
            Capability::Streaming => false,
        };
    }

    public function send(AiCall $call): AiRawResult
    {
        $payload = array_merge([
            'model'       => $call->model,
            'messages'    => $call->messages,
            'max_tokens'  => $call->maxTokens,
            'temperature' => $call->temperature,
        ], $this->extraPayload($call->model));

        if ($call->jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = $this->httpClient($call->timeoutSeconds)->post($this->endpoint(), $payload);

            if ($response->status() === 429) {
                return AiRawResult::fail(ErrorNormalizer::QUOTA_EXCEEDED);
            }

            if ($response->failed()) {
                $status  = $response->status();
                $message = $response->json('error.message') ?? 'Unknown error';

                Log::error("{$this->name()} API failed", ['status' => $status, 'error' => $message]);

                return AiRawResult::fail(ErrorNormalizer::fromHttpFailure($status, $message));
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                Log::warning("{$this->name()} response missing expected structure");

                return AiRawResult::fail(ErrorNormalizer::INVALID_FORMAT);
            }

            $text   = ErrorNormalizer::stripThink(trim($data['choices'][0]['message']['content']));
            $tokens = $data['usage']['total_tokens'] ?? null;

            if ($text === '') {
                return AiRawResult::fail(ErrorNormalizer::EMPTY_RESPONSE);
            }

            return AiRawResult::ok($text, $tokens);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("{$this->name()} connection timeout", ['message' => $e->getMessage()]);

            return AiRawResult::fail(ErrorNormalizer::CONNECTION_TIMEOUT);
        } catch (\Exception $e) {
            Log::error("{$this->name()} unexpected error", ['message' => $e->getMessage()]);

            return AiRawResult::fail($e->getMessage());
        }
    }
}
