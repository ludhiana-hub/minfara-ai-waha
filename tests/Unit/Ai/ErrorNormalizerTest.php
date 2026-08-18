<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Support\ErrorNormalizer;
use PHPUnit\Framework\TestCase;

class ErrorNormalizerTest extends TestCase
{
    public function test_429_maps_to_quota_exceeded(): void
    {
        $this->assertSame('quota_exceeded', ErrorNormalizer::fromHttpFailure(429, 'rate limited'));
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
}
