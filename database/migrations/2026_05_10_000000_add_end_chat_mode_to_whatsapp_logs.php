<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Modify enum to add 'end_chat' mode
            \DB::statement("ALTER TABLE `whatsapp_logs` MODIFY `mode` ENUM('faq', 'ai', 'error', 'end_chat')");
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Revert to original enum
            \DB::statement("ALTER TABLE `whatsapp_logs` MODIFY `mode` ENUM('faq', 'ai', 'error')");
        });
    }
};
