<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Support\ErrorNormalizer;
use PHPUnit\Framework\TestCase;

class ErrorNormalizerTest extends TestCase
{
    public function test_classify_429_with_short_retry_after_is_rate_limited(): void
    {
        $result = ErrorNormalizer::classify429(20, 'rate limit reached');

        $this->assertSame(ErrorNormalizer::RATE_LIMITED, $result['type']);
        $this->assertSame(20, $result['cooldownHint']);
    }

    public function test_classify_429_with_long_retry_after_is_quota_exceeded(): void
    {
        $result = ErrorNormalizer::classify429(7200, 'try again later');

        $this->assertSame(ErrorNormalizer::QUOTA_EXCEEDED, $result['type']);
        $this->assertSame(7200, $result['cooldownHint']);
    }

    public function test_classify_429_without_hint_but_quota_keyword_is_quota_exceeded(): void
    {
        $result = ErrorNormalizer::classify429(null, 'You have exceeded your current quota, please check your billing details.');

        $this->assertSame(ErrorNormalizer::QUOTA_EXCEEDED, $result['type']);
        $this->assertNull($result['cooldownHint']);
    }

    public function test_classify_429_without_hint_and_generic_message_is_rate_limited(): void
    {
        $result = ErrorNormalizer::classify429(null, 'rate limited');

        $this->assertSame(ErrorNormalizer::RATE_LIMITED, $result['type']);
        $this->assertNull($result['cooldownHint']);
    }

    public function test_other_status_keeps_provider_message(): void
    {
        $this->assertSame('model decommissioned', ErrorNormalizer::fromHttpFailure(400, 'model decommissioned'));
    }

    public function test_null_message_falls_back_to_unknown_error(): void
    {
        $this->assertSame('Unknown error', ErrorNormalizer::fromHttpFailure(500, null));
    }

    public function test_strip_think_removes_reasoning_block(): void
    {
        $this->assertSame('the answer', ErrorNormalizer::stripThink('<think>reasoning...</think>the answer'));
    }

    public function test_strip_think_is_noop_without_a_think_block(): void
    {
        $this->assertSame('plain text', ErrorNormalizer::stripThink('plain text'));
    }

    public function test_looks_like_unsupported_json_mode_matches_common_phrasings(): void
    {
        $this->assertTrue(ErrorNormalizer::looksLikeUnsupportedJsonMode('response_format not supported for this model'));
        $this->assertTrue(ErrorNormalizer::looksLikeUnsupportedJsonMode('json_object mode is unsupported'));
        $this->assertFalse(ErrorNormalizer::looksLikeUnsupportedJsonMode('model decommissioned'));
    }

    public function test_401_maps_to_auth_error(): void
    {
        $this->assertSame('auth_error', ErrorNormalizer::fromHttpFailure(401, 'invalid api key'));
    }

    public function test_403_maps_to_auth_error(): void
    {
        $this->assertSame('auth_error', ErrorNormalizer::fromHttpFailure(403, 'forbidden'));
    }

    public function test_looks_like_raw_reasoning_catches_indonesian_openers(): void
    {
        $this->assertTrue(ErrorNormalizer::looksLikeRawReasoning('Saya perlu menjawab pertanyaan ini dengan hati-hati.'));
        $this->assertTrue(ErrorNormalizer::looksLikeRawReasoning('Pengguna bertanya soal harga, jadi saya harus jelaskan.'));
        $this->assertFalse(ErrorNormalizer::looksLikeRawReasoning('Untuk program Bahasa Korea, harganya mulai Rp189k.'));
    }

    public function test_looks_like_raw_reasoning_catches_numbered_scratchpad_leak(): void
    {
        // Real production leak (2026-08-19): a "pricelist" request got this sent verbatim to
        // the customer instead of an answer — the model narrated its scratchpad as plain
        // prose with bold numbered meta-steps, no <think> tag and no opener phrase the old
        // regex covered.
        $leak = "Here's a thinking process:\n\n1. **Analyze User Input:** User said \"pricelist\". "
            . "This is a request for pricing information.\n2. **Identify Intent:** The user wants "
            . "to see the price list/packages for Languages by Fara.\n3. **Check Constraints & "
            . "Guidelines:**\n- Must use knowledge base for pricing.";

        $this->assertTrue(ErrorNormalizer::looksLikeRawReasoning($leak));
    }

    public function test_looks_like_raw_reasoning_catches_mid_text_reasoning_tells(): void
    {
        $this->assertTrue(ErrorNormalizer::looksLikeRawReasoning('Wait, the formatting is messy. Let me re-read carefully.'));
        $this->assertTrue(ErrorNormalizer::looksLikeRawReasoning('Actually, the text says the price is different.'));
    }

    public function test_looks_like_raw_reasoning_catches_novel_numbered_scratchpad_by_structure(): void
    {
        // Vocabulary-agnostic net: any "N. **Verb Noun:**" numbered meta-header is scratchpad
        // shape regardless of which specific verbs a rotating free model happens to use — this
        // must catch phrasing NOT in the phrase list above.
        $leak = "1. **Verify Requirements:** Check the input.\n2. **Draft Response:** Write the answer.";
        $this->assertTrue(ErrorNormalizer::looksLikeRawReasoning($leak));
    }

    public function test_looks_like_raw_reasoning_does_not_flag_legit_bold_price_list(): void
    {
        $legit = "Ini paket yang tersedia kak:\n1. **Lifetime Basic** - Rp199k\n2. **Lifetime+10** - Rp299k\nMau checkout yang mana?";
        $this->assertFalse(ErrorNormalizer::looksLikeRawReasoning($legit));
    }

    public function test_looks_like_raw_reasoning_does_not_flag_legit_plain_numbered_list(): void
    {
        $legit = "Untuk Bahasa Korea ada beberapa opsi:\n1. Paket 2 bulan Rp189k\n2. Paket 6 bulan Rp209k\nMau pilih yang mana kak?";
        $this->assertFalse(ErrorNormalizer::looksLikeRawReasoning($legit));
    }
}
