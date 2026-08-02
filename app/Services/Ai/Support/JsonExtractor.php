<?php

namespace App\Services\Ai\Support;

/**
 * Hardened successor to ConversationAnalysisService::parseJson()'s fence-strip + regex half.
 * The old `preg_match('/\{.*\}/s')` is greedy and over-captures when a model emits prose
 * containing braces, or two JSON objects back to back — this does a balanced-delimiter scan
 * that respects string literals instead.
 */
final class JsonExtractor
{
    public static function extract(string $raw): ?array
    {
        $clean = self::stripFences(trim($raw));
        $clean = ErrorNormalizer::stripThink($clean);

        $block = self::firstBalancedBlock($clean);
        if ($block === null) {
            return null;
        }

        $decoded = json_decode($block, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Repair pass: trailing commas before a closing bracket, curly/smart quotes.
        $repaired = preg_replace('/,(\s*[\]}])/', '$1', $block);
        $repaired = str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], $repaired);

        $decoded = json_decode($repaired, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function stripFences(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        return trim($text);
    }

    /**
     * Scan for the first complete {...} or [...] block, respecting string literals and
     * escape sequences so braces/brackets inside strings don't throw off the balance count.
     */
    private static function firstBalancedBlock(string $text): ?string
    {
        $len = strlen($text);
        $start = null;
        $openChar = null;
        $closeChar = null;

        for ($i = 0; $i < $len; $i++) {
            if ($text[$i] === '{' || $text[$i] === '[') {
                $start = $i;
                $openChar = $text[$i];
                $closeChar = $openChar === '{' ? '}' : ']';
                break;
            }
        }

        if ($start === null) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($i = $start; $i < $len; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }

            if ($char === $openChar) {
                $depth++;
            } elseif ($char === $closeChar) {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        // Unbalanced (truncated output) — nothing usable.
        return null;
    }
}
