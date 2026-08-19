<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only raw ENUM alter — SQLite (used by the test suite, DB_CONNECTION=sqlite in
        // phpunit.xml) has no ENUM type and errors on this syntax. SQLite stores `mode` as a
        // plain string column already, so there's nothing to alter there; skip rather than fail.
        if (\DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Modify enum to add 'end_chat' mode
            \DB::statement("ALTER TABLE `whatsapp_logs` MODIFY `mode` ENUM('faq', 'ai', 'error', 'end_chat')");
        });
    }

    public function down(): void
    {
        if (\DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Revert to original enum
            \DB::statement("ALTER TABLE `whatsapp_logs` MODIFY `mode` ENUM('faq', 'ai', 'error')");
        });
    }
};
