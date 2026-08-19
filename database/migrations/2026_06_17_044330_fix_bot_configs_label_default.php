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
        // MySQL-only raw ALTER — SQLite (used by the test suite, DB_CONNECTION=sqlite in
        // phpunit.xml) has no MODIFY COLUMN syntax; the column is already created nullable
        // there by the original create-table migration, so there's nothing to fix.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `label` VARCHAR(255) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `label` VARCHAR(255) NOT NULL");
    }
};
