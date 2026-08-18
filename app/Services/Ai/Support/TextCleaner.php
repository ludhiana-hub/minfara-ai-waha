<?php

namespace App\Services\Ai\Support;

/**
 * Strips WhatsApp/FAQ formatting cruft (menu prompts, divider lines, bold markers, extra
 * whitespace) out of FaqMenu content before it's fed to the AI as plain-text knowledge —
 * shared by BuildFaqDigestJob and RebuildKnowledgeIndexJob, which both needed the exact
 * same cleanup.
 */
final class TextCleaner
{
    public static function cleanFaqContent(string $text): string
    {
        $text = preg_replace('/Ketik \*[^*]+\*[^\n]*/iu', '', $text);
        $text = preg_replace('/[─]+/u', '', $text);
        $text = preg_replace('/\*([^*\n]+)\*/u', '$1', $text);
        $text = preg_replace('/\n+/', ' ', trim($text));

        return trim(preg_replace('/\s{2,}/', ' ', $text));
    }
}
