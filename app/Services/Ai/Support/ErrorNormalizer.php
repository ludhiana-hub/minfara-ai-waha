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
            '/^(we need to|we should|we must|let\'s|let me|here\'?s (a|the|my) (thinking|reasoning|thought) process|the user (says|asks|wants)|according to (the )?(instruction|guideline)|i (should|need to|will)|first,? (i|we)|okay,? (so|let))/i',
            $text
        )) {
            return true;
        }

        // Same class of leak, but the model reasoned in Indonesian instead of English — the
        // bot only ever answers Indonesian customers directly, so an opener like this is never
        // a legitimate reply, it's chain-of-thought narration.
        if (preg_match(
            '/^(saya (perlu|harus|akan)|pengguna (bertanya|meminta|ingin)|mari saya|baiklah,? (saya|mari)|pertama,? (saya|kita)|berdasarkan (instruksi|pedoman)|oke,? (jadi|mari))/i',
            $text
        )) {
            return true;
        }

        // Structural tells that show up ANYWHERE in the reply, not just as an opener — a real
        // production leak (numbered "1. **Analyze User Input:** / 2. **Identify Intent:**"
        // scratchpad, "Wait, the formatting is messy. Let me re-read carefully:") slipped past
        // the opener-only check above because the model didn't start with one of the phrases
        // there. These phrases are never legitimate in a WhatsApp sales reply, so a single hit
        // anywhere is enough to reject the whole response rather than relay it to the customer.
        if (preg_match(
            '/\b(thinking process|analyze user input|identify intent|check constraints|let me (extract|re-read|double-check|verify)|wait,?\s|actually,? the text says|hold on,)\b/i',
            $text
        )) {
            return true;
        }

        // Vocabulary-agnostic net: a numbered line with a bold meta-label ending in a colon
        // ("1. **Analyze User Input:**", "2. **Check Constraints:**") is the generic shape of an
        // LLM scratchpad, regardless of which specific verb it uses — free/rotating models
        // (openrouter/free) can phrase their reasoning differently every time, so matching the
        // STRUCTURE catches variants the phrase list above hasn't seen yet. A real WA sales
        // reply never has a numbered step with a bolded "Verb Noun:" label — FAQ-style numbered
        // lists in this bot's replies use plain text, not this scratchpad shape.
        return (bool) preg_match('/^\s{0,3}\d+\.\s*\*\*[^*\n]{3,60}:\*\*/mu', $text);
    }

    public static function looksLikeUnsupportedJsonMode(string $error): bool
    {
        return (bool) preg_match('/response_format|json_object|not supported|unsupported.*format/i', $error);
    }
}
