<?php

namespace App\Services\Ai\Support;

/**
 * Local (no API call) cleanup for replies the model never finished. Used when a provider
 * reports it stopped because it hit max_tokens — re-asking would spend another request
 * against the RPM/TPM budget and would very likely hit the same ceiling again.
 */
final class ReplyTrimmer
{
    // Never throw away more than this share of the reply just to reach a clean cut point —
    // a reply that is mostly one long unterminated sentence is better left whole.
    private const MIN_KEEP_RATIO = 0.3;

    // Sentence terminator, optionally followed by closing quote/bracket/markdown marks and
    // emoji, then whitespace or end of text. Matches where a clean cut may be made.
    private const BOUNDARY = '/[.!?…](?:["\')\]*_~]|[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}])*(?=\s|$)/u';

    public static function toCompleteSentence(string $text): string
    {
        $text = rtrim($text);

        if ($text === '' || preg_match(self::BOUNDARY . 'D', mb_substr($text, -4)) === 1) {
            return $text;
        }

        $minKeep = (int) floor(mb_strlen($text) * self::MIN_KEEP_RATIO);
        $cut     = null;

        if (preg_match_all(self::BOUNDARY, $text, $m, PREG_OFFSET_CAPTURE)) {
            $last = end($m[0]);
            $cut  = $last[1] + strlen($last[0]); // byte offset, matches substr() below
        }

        // A list or paragraph break is also a clean cut and can come after the last sentence
        // terminator (e.g. an unterminated "- Item" line followed by a half-written one).
        $newline = strrpos($text, "\n");
        if ($newline !== false && ($cut === null || $newline > $cut)) {
            $cut = $newline;
        }

        if ($cut === null || mb_strlen(substr($text, 0, $cut)) < $minKeep) {
            return $text;
        }

        return rtrim(substr($text, 0, $cut));
    }
}
