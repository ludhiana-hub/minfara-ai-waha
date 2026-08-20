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
                $retryAfter = self::parseRetryAfter($response->header('Retry-After'));
                $classified = ErrorNormalizer::classify429($retryAfter, $response->json('error.message'));

                return AiRawResult::fail($classified['type'], $classified['cooldownHint']);
            }

            if ($response->failed()) {
                $status  = $response->status();
                $message = $response->json('error.message') ?? 'Unknown error';

                // 401/403 = dead/revoked key, needs a human to fix — logged at critical so it's
                // distinguishable from a transient provider outage, which self-heals.
                if ($status === 401 || $status === 403) {
                    Log::critical("{$this->name()} API key rejected — needs manual rotation", ['status' => $status, 'error' => $message]);
                } else {
                    Log::error("{$this->name()} API failed", ['status' => $status, 'error' => $message]);
                }

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

            if (ErrorNormalizer::looksLikeRawReasoning($text)) {
                Log::warning("{$this->name()} returned raw reasoning instead of a reply", ['model' => $call->model]);

                return AiRawResult::fail(ErrorNormalizer::REASONING_LEAK);
            }

            return AiRawResult::ok($text, $tokens);
        } catch (\Illuminate\Http\Client\ConnectionException|\GuzzleHttp\Exception\ConnectException $e) {
            // Guzzle can throw ConnectException directly (not wrapped as Laravel's
            // ConnectionException) for some transient failures — e.g. DNS blip or connection
            // reset before the timeout window closes. Both are treated as retryable so
            // AiRouter's retry_on match actually fires instead of silently giving up.
            Log::error("{$this->name()} connection timeout", ['message' => $e->getMessage()]);

            return AiRawResult::fail(ErrorNormalizer::CONNECTION_TIMEOUT);
        } catch (\Exception $e) {
            Log::error("{$this->name()} unexpected error", ['message' => $e->getMessage()]);

            return AiRawResult::fail($e->getMessage());
        }
    }

    /**
     * Retry-After per RFC 9110 is either a delay in seconds or an HTTP-date — the API providers
     * used here (Groq, OpenRouter) only ever send the delay-in-seconds form, so the HTTP-date
     * case is left as null (caller falls back to sniffing the error message instead).
     */
    protected static function parseRetryAfter(?string $header): ?int
    {
        if ($header === null || !ctype_digit(trim($header))) {
            return null;
        }

        return (int) trim($header);
    }
}
