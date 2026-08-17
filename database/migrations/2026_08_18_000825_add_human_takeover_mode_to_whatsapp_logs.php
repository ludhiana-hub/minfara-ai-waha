<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Adds 'human_takeover' — WhatsAppController::handleOwnerReply() (line 371) has
            // always written this value, but it was never in the enum, causing
            // SQLSTATE[01000]: Data truncated warnings AND silently breaking the two features
            // that already query it: HumanTakeoverController::index()'s recent-logs panel and
            // KnowledgeSynthesizerJob::buildHumanExamples().
            \DB::statement("ALTER TABLE `whatsapp_logs` MODIFY `mode` ENUM('faq', 'ai', 'error', 'end_chat', 'human_takeover')");
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `whatsapp_logs` MODIFY `mode` ENUM('faq', 'ai', 'error', 'end_chat')");
        });
    }
};
