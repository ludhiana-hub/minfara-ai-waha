<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        // Ganti semua sisa "Deutsch lernen mit Fara" / "Deutsch Lernen mit Fara"
        // di tabel bot_configs yang belum terupdate di production
        $keys = ['footer_ai', 'footer_faq', 'fallback_message', 'ai_system_prompt', 'rate_limit_message'];

        foreach ($keys as $key) {
            $row = DB::table('bot_configs')->where('key', $key)->first();
            if (!$row) {
                continue;
            }

            $newValue = str_ireplace(
                ['Deutsch lernen mit Fara', 'Deutsch Lernen mit Fara', 'DlmF', 'DLMF'],
                ['Languages by Fara',       'Languages by Fara',       'LBF',  'LBF'],
                $row->value
            );

            if ($newValue !== $row->value) {
                DB::table('bot_configs')->where('key', $key)->update(['value' => $newValue]);
                Cache::forget('bot_config_' . $key);
            }
        }

        // Pastikan footer_ai sudah benar
        $footerAi = DB::table('bot_configs')->where('key', 'footer_ai')->value('value');
        if ($footerAi && !str_contains($footerAi, 'Languages by Fara')) {
            DB::table('bot_configs')->where('key', 'footer_ai')->update([
                'value' => "─────────────────\n_MinFara AI_ 🤖 _· Languages by Fara_\nKetik *0* menu utama | *99* hubungi admin",
            ]);
            Cache::forget('bot_config_footer_ai');
        }
    }

    public function down(): void
    {
        // Tidak perlu rollback — ini perbaikan permanen
    }
};
