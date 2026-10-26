<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Support\ReplyTrimmer;
use PHPUnit\Framework\TestCase;

class ReplyTrimmerTest extends TestCase
{
    public function test_cuts_back_to_last_complete_sentence(): void
    {
        $text = 'Paket A harga 100rb. Paket B harga 200rb dan terma';

        $this->assertSame('Paket A harga 100rb.', ReplyTrimmer::toCompleteSentence($text));
    }

    public function test_keeps_a_trailing_emoji_attached_to_the_last_sentence(): void
    {
        $text = "Siap kak, aku bantu ya! 😊\nUntuk paket yang sesuai kamu perlu dul";

        $this->assertSame("Siap kak, aku bantu ya! 😊", ReplyTrimmer::toCompleteSentence($text));
    }

    public function test_prefers_the_last_complete_line_for_lists(): void
    {
        $text = "Pilihan paket:\n- Bulanan Rp100rb\n- Lifetime Rp5";

        $this->assertSame("Pilihan paket:\n- Bulanan Rp100rb", ReplyTrimmer::toCompleteSentence($text));
    }

    public function test_leaves_text_alone_when_no_safe_cut_point_exists(): void
    {
        $text = 'Halo kak selamat datang di Languages by Fara kami punya banyak';

        $this->assertSame($text, ReplyTrimmer::toCompleteSentence($text));
    }

    public function test_does_not_discard_most_of_the_reply_to_reach_a_cut_point(): void
    {
        // The only terminator is at the very start — cutting there would throw away ~95%.
        $text = 'Hai. ' . str_repeat('kata ', 30);

        $this->assertSame(rtrim($text), ReplyTrimmer::toCompleteSentence($text));
    }

    public function test_already_complete_text_is_unchanged(): void
    {
        $this->assertSame('Sudah lengkap kok kak.', ReplyTrimmer::toCompleteSentence('Sudah lengkap kok kak.'));
    }
}
