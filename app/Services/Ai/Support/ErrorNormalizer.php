<?php

namespace App\Services\Ai\Support;

/**
 * Straight extraction of the error-normalization ladder that used to be duplicated across
 * OpenAiCompatibleService and GeminiService — kept byte-identical so AiRouter's branching
 * (retry-on-timeout, quota_exceeded handling, etc.) sees the exact same strings as before.
 */
final class ErrorNormalizer
{
    public const QUOTA_EXCEEDED     = 'quota_exceeded';
    public const CONNECTION_TIMEOUT = 'Connection timeout';
    public const INVALID_FORMAT     = 'Invalid response format';
    public const EMPTY_RESPONSE     = 'Empty response';
    public const JSON_PARSE_FAILED  = 'json_parse_failed';
    public const TOOL_LOOP_EXHAUSTED = 'tool_loop_exhausted';

    public static function fromHttpFailure(int $status, ?string $message): string
    {
        if ($status === 429) {
            return self::QUOTA_EXCEEDED;
        }

        return $message ?? 'Unknown error';
    }

    /** <think>...</think> stripping — now applied uniformly to every provider (including Gemini,
     * which the pre-AiRouter GeminiService never stripped — a latent bug fixed here).
     * Also handles an unterminated <think> (max_tokens hit mid-reasoning): everything from
     * <think> onward is dropped rather than leaking raw chain-of-thought to the customer. */
    public static function stripThink(string $text): string
    {
        $text = preg_replace('/<think>.*?<\/think>/si', '', $text);
        $text = preg_replace('/<think>.*$/si', '', $text);

        return trim($text);
    }

    public static function looksLikeUnsupportedJsonMode(string $error): bool
    {
        return (bool) preg_match('/response_format|json_object|not supported|unsupported.*format/i', $error);
    }
}
