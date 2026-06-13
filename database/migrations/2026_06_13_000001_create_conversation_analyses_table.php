<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->string('contact_name', 100)->nullable();
            $table->date('session_date');
            $table->unsignedSmallInteger('messages_count')->default(0);

            // AI analysis result
            $table->string('topic', 200)->nullable();
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();
            $table->boolean('issue_detected')->default(false);
            $table->string('issue_type', 200)->nullable();
            $table->boolean('is_faq_gap')->default(false);
            $table->text('faq_gap_question')->nullable();
            $table->unsignedTinyInteger('purchase_intent_score')->nullable(); // 0-10
            $table->string('customer_segment', 50)->nullable();
            // hot_lead|window_shopper|objector|loyalist|churner_signal|unknown
            $table->string('channel_source', 100)->nullable();
            $table->json('keywords')->nullable();
            $table->text('summary')->nullable();
            $table->boolean('resolved')->default(true);

            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->unique(['phone_number', 'session_date']);
            $table->index('session_date');
            $table->index('sentiment');
            $table->index('customer_segment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_analyses');
    }
};
