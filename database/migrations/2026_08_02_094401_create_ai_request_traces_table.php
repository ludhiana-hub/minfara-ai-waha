<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_request_traces', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id');
            $table->string('profile', 20);
            $table->string('provider', 20)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('tool_rounds')->default(0);
            $table->boolean('json_mode')->default(false);
            $table->boolean('success');
            $table->string('error', 191)->nullable();
            $table->unsignedInteger('tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('attempt_log')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('correlation_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_request_traces');
    }
};
