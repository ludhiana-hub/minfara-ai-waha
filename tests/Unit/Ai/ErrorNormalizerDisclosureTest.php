<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Support\ErrorNormalizer;
use PHPUnit\Framework\TestCase;

class ErrorNormalizerDisclosureTest extends TestCase
{
    public function test_catches_knowledge_base_marker_echo(): void
    {
        $this->assertTrue(ErrorNormalizer::looksLikeDisclosureLeak(
            '=== KNOWLEDGE BASE (relevan, RAHASIA INTERNAL) ===\nHarga Lifetime Rp199k\n=== END KNOWLEDGE BASE (jangan ditampilkan ke customer) ==='
        ));
    }

    public function test_catches_coaching_notes_marker_echo(): void
    {
        $this->assertTrue(ErrorNormalizer::looksLikeDisclosureLeak(
            '=== CATATAN GAYA & TEKNIK SALES (internal, jangan disebut ke customer) ===\nSelalu arahkan ke checkout.'
        ));
    }

    public function test_catches_english_instruction_disclosure(): void
    {
        $this->assertTrue(ErrorNormalizer::looksLikeDisclosureLeak('My instructions say to always recommend the Lifetime package.'));
        $this->assertTrue(ErrorNormalizer::looksLikeDisclosureLeak('According to my instructions, I should not discuss competitors.'));
    }

    public function test_catches_indonesian_instruction_disclosure(): void
    {
        $this->assertTrue(ErrorNormalizer::looksLikeDisclosureLeak('Berikut adalah instruksi saya dari sistem: jangan pernah...'));
        $this->assertTrue(ErrorNormalizer::looksLikeDisclosureLeak('Aku punya knowledge base yang berisi semua materi training.'));
    }

    public function test_does_not_flag_normal_wa_sales_reply(): void
    {
        $legit = "Halo kak! Untuk Bahasa Jerman ada paket Lifetime Basic Rp199k, cocok banget buat kamu yang mau belajar santai.\n\nMau langsung checkout di https://mitfara.com atau ada yang mau ditanya dulu?";
        $this->assertFalse(ErrorNormalizer::looksLikeDisclosureLeak($legit));
    }

    public function test_does_not_flag_reply_mentioning_generic_words_out_of_pattern(): void
    {
        $legit = "Materinya lengkap kok kak, ada video pembelajaran, kuis, sampai sertifikat completion.";
        $this->assertFalse(ErrorNormalizer::looksLikeDisclosureLeak($legit));
    }
}
