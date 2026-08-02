<?php

namespace App\Services\Ai\Providers;

use App\Models\BotConfig;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiRawResult;
use App\Services\Ai\Capability;
use App\Services\Ai\Contracts\AiProviderContract;
use App\Services\Ai\Support\ErrorNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini's REST shape differs enough from the OpenAI-compatible providers (system prompt
 * as a separate top-level field, assistant→model role mapping, response nested under
 * candidates[]) that it implements the contract directly rather than extending the shared
 * base — same approach the old GeminiService took relative to OpenAiCompatibleService.
 */
class GeminiProvider implements AiProviderContract
{
    public function name(): string
    {
        return 'gemini';
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
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$call->model}:generateContent";

        [$systemPrompt, $contents] = $this->splitMessages($call->messages);

        $generationConfig = [
            'maxOutputTokens' => $call->maxTokens,
            'temperature'     => $call->temperature,
        ];

        if ($call->jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $payload = [
            'contents'         => $contents,
            'generationConfig' => $generationConfig,
        ];

        if ($systemPrompt !== null) {
            $payload['system_instruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        try {
            $response = Http::timeout($call->timeoutSeconds)
                ->post("{$endpoint}?key={$this->apiKey()}", $payload);

            if ($response->status() === 429) {
                return AiRawResult::fail(ErrorNormalizer::QUOTA_EXCEEDED);
            }

            if ($response->failed()) {
                $status  = $response->status();
                $message = $response->json('error.message') ?? 'Unknown error';

                Log::error('Gemini API failed', ['status' => $status, 'error' => $message]);

                return AiRawResult::fail(ErrorNormalizer::fromHttpFailure($status, $message));
            }

            $data = $response->json();

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                Log::warning('Gemini response missing expected structure');

                return AiRawResult::fail(ErrorNormalizer::INVALID_FORMAT);
            }

            $text   = ErrorNormalizer::stripThink(trim($data['candidates'][0]['content']['parts'][0]['text']));
            $tokens = $data['usageMetadata']['totalTokenCount'] ?? null;

            if ($text === '') {
                return AiRawResult::fail(ErrorNormalizer::EMPTY_RESPONSE);
            }

            return AiRawResult::ok($text, $tokens);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini connection timeout', ['message' => $e->getMessage()]);

            return AiRawResult::fail(ErrorNormalizer::CONNECTION_TIMEOUT);
        } catch (\Exception $e) {
            Log::error('Gemini unexpected error', ['message' => $e->getMessage()]);

            return AiRawResult::fail($e->getMessage());
        }
    }

    /**
     * @param array<int, array{role:string, content:string}> $messages
     * @return array{0: ?string, 1: array}
     */
    private function splitMessages(array $messages): array
    {
        $systemPrompt = null;
        $contents     = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemPrompt = $message['content'];
                continue;
            }

            $contents[] = [
                'role'  => $message['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message['content']]],
            ];
        }

        return [$systemPrompt, $contents];
    }

    private function apiKey(): string
    {
        return BotConfig::get('gemini_api_key') ?: (config('services.gemini.key') ?? '');
    }
}
