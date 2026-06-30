<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_suggestions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->unsignedSmallInteger('frequency')->default(1);
            $table->json('example_phones')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('high_priority')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_suggestions');
    }
};
