<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make label nullable so BotConfig::set() can INSERT new config keys
        // without crashing when the key was never pre-seeded (label was NOT NULL with no default).
        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `label` VARCHAR(255) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `label` VARCHAR(255) NOT NULL");
    }
};
