<?php

namespace Tests\Feature;

use App\Models\BotConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPromptMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_PAYMENT = '• *Pembayaran:* Bank Mandiri 130-00-2353540-7 atau BCA 4490365864 — a.n. PT Fara Kreatif Sejahtera';
    private const OLD_ACTIVATION = '• *Aktivasi akun:* maks 1×24 jam setelah pembayaran. Non-refundable. Tidak bisa cicil.';

    private function runMigration(): void
    {
        (require base_path('database/migrations/2026_10_26_000003_make_web_checkout_primary_payment_in_prompt.php'))->up();
        BotConfig::forget('ai_system_prompt');
    }

    public function test_web_checkout_becomes_the_primary_payment_and_manual_transfer_a_fallback(): void
    {
        BotConfig::set('ai_system_prompt', "intro\n" . self::OLD_PAYMENT . "\n" . self::OLD_ACTIVATION . "\noutro");

        $this->runMigration();
        $prompt = BotConfig::get('ai_system_prompt');

        $this->assertStringContainsString('utama lewat checkout di https://mitfara.com', $prompt);
        $this->assertStringContainsString('HANYA sebagai cadangan', $prompt);
        $this->assertStringContainsString('130-00-2353540-7', $prompt); // fallback details kept
        $this->assertStringContainsString("intro\n", $prompt);
        $this->assertStringContainsString("\noutro", $prompt);
    }

    public function test_is_idempotent(): void
    {
        BotConfig::set('ai_system_prompt', self::OLD_PAYMENT . "\n" . self::OLD_ACTIVATION);

        $this->runMigration();
        $once = BotConfig::get('ai_system_prompt');
        $this->runMigration();

        $this->assertSame($once, BotConfig::get('ai_system_prompt'));
    }

    public function test_a_prompt_an_admin_already_customised_is_left_alone(): void
    {
        BotConfig::set('ai_system_prompt', 'prompt custom admin tanpa baris pembayaran lama');

        $this->runMigration();

        $this->assertSame('prompt custom admin tanpa baris pembayaran lama', BotConfig::get('ai_system_prompt'));
    }
}
