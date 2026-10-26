<?php

use App\Models\BotConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private const OLD_PAYMENT = '• *Pembayaran:* Bank Mandiri 130-00-2353540-7 atau BCA 4490365864 — a.n. PT Fara Kreatif Sejahtera';
    private const NEW_PAYMENT = '• *Pembayaran:* utama lewat checkout di https://mitfara.com (metode pembayaran dipilih di halaman payment). Transfer manual Bank Mandiri 130-00-2353540-7 atau BCA 4490365864 a.n. PT Fara Kreatif Sejahtera HANYA sebagai cadangan jika customer tidak bisa bayar lewat web, jangan tawarkan duluan.';

    private const OLD_ACTIVATION = '• *Aktivasi akun:* maks 1×24 jam setelah pembayaran. Non-refundable. Tidak bisa cicil.';
    private const NEW_ACTIVATION = '• *Aktivasi akun:* setelah pembayaran di web, akun FlexiLearn dikirim ke email (beberapa menit hingga maks 1×24 jam). Non-refundable. Tidak bisa cicil.';

    public function up(): void
    {
        // Targeted replace of two lines instead of rewriting the whole prompt: closing now points
        // to the self-service web checkout, and the old "transfer to Mandiri/BCA" line made the AI
        // steer customers to manual payment. If an admin already edited these lines in the CMS the
        // exact text won't match and their version is left untouched.
        $prompt = BotConfig::get('ai_system_prompt');
        if (!is_string($prompt) || !str_contains($prompt, self::OLD_PAYMENT)) {
            Log::info('make_web_checkout_primary_payment_in_prompt: payment line not found, prompt left unchanged');

            return;
        }

        $prompt = str_replace([self::OLD_PAYMENT, self::OLD_ACTIVATION], [self::NEW_PAYMENT, self::NEW_ACTIVATION], $prompt);

        BotConfig::set('ai_system_prompt', $prompt);
    }

    public function down(): void
    {
        // Prompt content change, not schema; no rollback.
    }
};
