<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('from_number');
            $table->string('ip_address', 45)->nullable()->after('contact_name');
        });

        // Strip @c.us suffix from any existing records — MySQL-only function (SUBSTRING_INDEX),
        // and a one-time data cleanup with nothing to clean on a fresh test DB, so skip on
        // other drivers (SQLite, used by the test suite) rather than fail.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                "UPDATE whatsapp_logs SET from_number = SUBSTRING_INDEX(from_number, '@', 1) WHERE from_number LIKE '%@%'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'ip_address']);
        });
    }
};
