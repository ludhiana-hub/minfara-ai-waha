<?php

namespace Tests\Unit\Sales;

use App\Services\Sales\LeadSignalExtractor;
use PHPUnit\Framework\TestCase;

class LeadSignalExtractorTest extends TestCase
{
    public function test_picks_up_language_and_goal(): void
    {
        $s = LeadSignalExtractor::extract('Mau belajar bahasa Jerman buat kerja di luar negeri');

        $this->assertSame('Jerman', $s['language']);
        $this->assertSame('kerja', $s['goal']);
    }

    public function test_picks_up_cefr_level_and_absolute_beginner(): void
    {
        $this->assertSame('A1', LeadSignalExtractor::extract('level a1 aja kak')['level']);
        $this->assertSame('A2', LeadSignalExtractor::extract('aku udah bisa A2 kak')['level']);
        $this->assertSame('pemula', LeadSignalExtractor::extract('aku dari nol banget')['level']);
    }

    public function test_picks_up_package_interest(): void
    {
        $this->assertSame('Lifetime', LeadSignalExtractor::extract('lifetime berapa ya?')['package']);
        $this->assertSame('Bulanan', LeadSignalExtractor::extract('yang 6 bulan aja deh')['package']);
        $this->assertSame('Private', LeadSignalExtractor::extract('ada kelas private ga?')['package']);
    }

    public function test_detects_objections(): void
    {
        $this->assertSame('harga', LeadSignalExtractor::extract('wah agak mahal ya')['objection']);
        $this->assertSame('ragu', LeadSignalExtractor::extract('masih mikir-mikir dulu deh')['objection']);
        $this->assertSame('ribet', LeadSignalExtractor::extract('daftarnya ribet ga sih?')['objection']);
    }

    public function test_detects_buying_intent(): void
    {
        $this->assertTrue(LeadSignalExtractor::extract('oke gimana cara daftar?')['ready']);
        $this->assertTrue(LeadSignalExtractor::extract('mau beli yang lifetime')['ready']);
        $this->assertTrue(LeadSignalExtractor::extract('cara bayarnya gimana')['ready']);
    }

    public function test_a_plain_question_has_no_signals(): void
    {
        $this->assertSame([], LeadSignalExtractor::extract('jam buka admin kapan?'));
    }

    public function test_unrelated_words_do_not_trigger_false_positives(): void
    {
        // "bulan" alone (e.g. a date) is not a package choice; "kerjakan" is not a goal statement
        $this->assertArrayNotHasKey('package', LeadSignalExtractor::extract('tanggal berapa bulan ini?'));
        $this->assertArrayNotHasKey('ready', LeadSignalExtractor::extract('jelaskan perbedaan paketnya'));
    }
}
