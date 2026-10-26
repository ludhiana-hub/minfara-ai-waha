<?php

use App\Models\BotConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // firstOrCreate: existing installs keep FAQ replies on by default, and a value an
        // admin already set directly in the DB is never overwritten.
        BotConfig::firstOrCreate(['key' => 'faq_enabled'], [
            'value'       => 'true',
            'type'        => 'boolean',
            'label'       => 'Aktifkan Balasan FAQ',
            'description' => 'Jika nonaktif, command FAQ tidak dibalas. Kalau AI juga nonaktif, bot diam total ke chat masuk (notifikasi keluar tetap jalan).',
            'group'       => 'general',
        ]);
    }

    public function down(): void
    {
        BotConfig::where('key', 'faq_enabled')->delete();
    }
};
