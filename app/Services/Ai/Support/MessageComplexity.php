<?php

namespace App\Services\Ai\Support;

/**
 * Cheap, synchronous per-message complexity heuristic — NOT an LLM call. Used by
 * ProcessAiReply right before AiRouter::run() to right-size max_tokens and add a one-off
 * brevity/detail instruction for THIS turn only (never persisted to BotConfig). Ambiguous
 * input always falls through to DEFAULT — today's unchanged flat ai_max_tokens behavior —
 * so this can only make a reply cheaper, never worse, than before.
 */
final class MessageComplexity
{
    public const SHORT   = 'short';
    public const LONG    = 'long';
    public const DEFAULT = 'default';

    private const DETAIL_KEYWORDS = '/\b(jelaskan|jelasin|dijelaskan|detail|detil|lengkap|selengkapnya|bagaimana cara|gimana cara|kenapa|mengapa|apa saja|apa aja|perbedaan|bedanya|dibandingkan|perbandingan)\b/iu';

    public static function classify(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return self::DEFAULT;
        }

        $wordCount     = count(array_filter(preg_split('/\s+/u', $message)));
        $questionMarks = substr_count($message, '?');
        $sentenceEnds  = preg_match_all('/[.!?]/u', $message);

        if ($wordCount > 25 || $questionMarks >= 2 || $sentenceEnds >= 3 || preg_match(self::DETAIL_KEYWORDS, $message)) {
            return self::LONG;
        }

        if ($wordCount <= 6 && mb_strlen($message) <= 40) {
            return self::SHORT;
        }

        return self::DEFAULT;
    }
}
