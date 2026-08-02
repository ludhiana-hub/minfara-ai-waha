<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Support\JsonExtractor;
use PHPUnit\Framework\TestCase;

class JsonExtractorTest extends TestCase
{
    public function test_plain_object(): void
    {
        $this->assertSame(['a' => 1], JsonExtractor::extract('{"a": 1}'));
    }

    public function test_strips_markdown_fences(): void
    {
        $this->assertSame(['a' => 1], JsonExtractor::extract("```json\n{\"a\": 1}\n```"));
        $this->assertSame(['a' => 1], JsonExtractor::extract("```\n{\"a\": 1}\n```"));
    }

    public function test_prose_wrapped_json_is_extracted(): void
    {
        $raw = 'Sure, here is the JSON you asked for: {"a": 1} — let me know if you need anything else.';
        $this->assertSame(['a' => 1], JsonExtractor::extract($raw));
    }

    public function test_does_not_over_capture_across_two_objects(): void
    {
        // The old greedy regex /\{.*\}/s would swallow BOTH objects and everything between
        // them into one invalid blob. The balanced scanner must stop at the first one.
        $raw = '{"a": 1} some text in between {"b": 2}';
        $this->assertSame(['a' => 1], JsonExtractor::extract($raw));
    }

    public function test_braces_inside_string_values_do_not_break_balance(): void
    {
        $raw = '{"a": "text with a { brace } inside it", "b": 2}';
        $this->assertSame(['a' => 'text with a { brace } inside it', 'b' => 2], JsonExtractor::extract($raw));
    }

    public function test_top_level_array(): void
    {
        $this->assertSame([['q' => 'x', 'a' => 'y']], JsonExtractor::extract('[{"q":"x","a":"y"}]'));
    }

    public function test_trailing_comma_is_repaired(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], JsonExtractor::extract('{"a": 1, "b": 2,}'));
    }

    public function test_truncated_output_returns_null(): void
    {
        $this->assertNull(JsonExtractor::extract('{"a": 1, "b": '));
    }

    public function test_no_json_at_all_returns_null(): void
    {
        $this->assertNull(JsonExtractor::extract('Maaf, saya tidak bisa membantu dengan itu.'));
    }

    public function test_think_block_is_stripped_before_extraction(): void
    {
        $raw = "<think>let me reason about this...</think>\n{\"a\": 1}";
        $this->assertSame(['a' => 1], JsonExtractor::extract($raw));
    }

    public function test_escaped_quote_inside_string_does_not_end_string_early(): void
    {
        $raw = '{"a": "he said \"hi\"", "b": 2}';
        $this->assertSame(['a' => 'he said "hi"', 'b' => 2], JsonExtractor::extract($raw));
    }
}
