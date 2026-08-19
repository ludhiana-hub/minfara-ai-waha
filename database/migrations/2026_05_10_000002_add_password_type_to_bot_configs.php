<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only raw ENUM alter — SQLite (used by the test suite, DB_CONNECTION=sqlite in
        // phpunit.xml) has no ENUM type and errors on this syntax; `type` is already a plain
        // string column there, so there's nothing to alter — skip rather than fail.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `type` ENUM('text','textarea','boolean','number','password') DEFAULT 'text'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `type` ENUM('text','textarea','boolean','number') DEFAULT 'text'");
    }
};
