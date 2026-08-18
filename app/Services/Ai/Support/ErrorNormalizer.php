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
    public const REASONING_LEAK      = 'reasoning_leak';
    public const AUTH_ERROR          = 'auth_error';

    public static function fromHttpFailure(int $status, ?string $message): string
    {
        if ($status === 429) {
            return self::QUOTA_EXCEEDED;
        }

        // 401/403 means the API key is dead/revoked, not a transient outage — the circuit
        // breaker still applies the same cooldown either way, but callers can use this constant
        // to log/alert distinctly so a dead key gets human attention instead of being mistaken
        // for a self-healing provider blip.
        if ($status === 401 || $status === 403) {
            return self::AUTH_ERROR;
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

    /**
     * Some free/reasoning-style models (esp. via openrouter/free's rotating pool) narrate
     * their chain-of-thought as plain English prose instead of wrapping it in <think> tags —
     * stripThink() can't touch that, since there's no tag to find. The bot only ever answers
     * in Indonesian, so an English response opening with a meta-cognition phrase like
     * "We need to respond..." or "The user says..." is never a legitimate customer-facing
     * reply — it's the model thinking out loud. Caught here and treated as a failed attempt
     * so AiRouter falls through to the next model/provider instead of relaying it.
     */
    public static function looksLikeRawReasoning(string $text): bool
    {
        $text = trim($text);

        if (preg_match(
            '/^(we need to|we should|we must|let\'s|let me|the user (says|asks|wants)|according to (the )?(instruction|guideline)|i (should|need to|will)|first,? (i|we)|okay,? (so|let))/i',
            $text
        )) {
            return true;
        }

        // Same class of leak, but the model reasoned in Indonesian instead of English — the
        // bot only ever answers Indonesian customers directly, so an opener like this is never
        // a legitimate reply, it's chain-of-thought narration.
        return (bool) preg_match(
            '/^(saya (perlu|harus|akan)|pengguna (bertanya|meminta|ingin)|mari saya|baiklah,? (saya|mari)|pertama,? (saya|kita)|berdasarkan (instruksi|pedoman)|oke,? (jadi|mari))/i',
            $text
        );
    }

    public static function looksLikeUnsupportedJsonMode(string $error): bool
    {
        return (bool) preg_match('/response_format|json_object|not supported|unsupported.*format/i', $error);
    }
}
