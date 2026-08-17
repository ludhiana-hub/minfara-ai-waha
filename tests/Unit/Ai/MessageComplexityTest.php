<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Support\MessageComplexity;
use PHPUnit\Framework\TestCase;

class MessageComplexityTest extends TestCase
{
    public function test_empty_message_is_default(): void
    {
        $this->assertSame(MessageComplexity::DEFAULT, MessageComplexity::classify(''));
        $this->assertSame(MessageComplexity::DEFAULT, MessageComplexity::classify('   '));
    }

    public function test_short_transactional_questions_are_short(): void
    {
        $this->assertSame(MessageComplexity::SHORT, MessageComplexity::classify('harga?'));
        $this->assertSame(MessageComplexity::SHORT, MessageComplexity::classify('ada promo'));
        $this->assertSame(MessageComplexity::SHORT, MessageComplexity::classify('jam buka?'));
    }

    public function test_detailed_questions_are_long(): void
    {
        $this->assertSame(
            MessageComplexity::LONG,
            MessageComplexity::classify('jelaskan detail perbedaan paket Lifetime sama bulanan dong, kenapa lifetime lebih baik?')
        );
        $this->assertSame(MessageComplexity::LONG, MessageComplexity::classify('bagaimana cara daftar dan bayarnya gimana ya kak?'));
    }

    public function test_long_word_count_triggers_long(): void
    {
        $message = str_repeat('kata ', 26);
        $this->assertSame(MessageComplexity::LONG, MessageComplexity::classify($message));
    }

    public function test_multiple_question_marks_triggers_long(): void
    {
        $this->assertSame(MessageComplexity::LONG, MessageComplexity::classify('ini gimana? terus ini juga gimana?'));
    }

    public function test_ambiguous_medium_message_is_default(): void
    {
        $this->assertSame(MessageComplexity::DEFAULT, MessageComplexity::classify('halo min, aku mau tanya soal paket bahasa Jerman'));
    }
}
