<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date')->unique();
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('unique_customers')->default(0);
            $table->decimal('bot_effectiveness_rate', 5, 2)->default(0); // % resolved
            $table->unsignedInteger('sentiment_positive')->default(0);
            $table->unsignedInteger('sentiment_neutral')->default(0);
            $table->unsignedInteger('sentiment_negative')->default(0);
            $table->json('top_topics')->nullable();       // [{topic, count}]
            $table->json('faq_gaps')->nullable();         // [{question, count}]
            $table->decimal('avg_intent_score', 3, 1)->default(0);
            $table->json('customer_segments')->nullable(); // {hot_lead: n, ...}
            $table->json('channel_sources')->nullable();   // {TikTok: n, ...}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_summaries');
    }
};
