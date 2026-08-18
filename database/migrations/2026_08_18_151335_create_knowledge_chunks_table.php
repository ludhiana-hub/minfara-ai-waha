<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['faq_menu', 'knowledge_suggestion', 'training_material']);
            $table->unsignedBigInteger('source_id');
            $table->text('content');
            $table->string('content_hash', 32);
            $table->json('embedding');
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
