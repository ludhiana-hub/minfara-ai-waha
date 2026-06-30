<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paused_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 30)->unique();
            $table->string('contact_name', 100)->nullable();
            $table->timestamp('paused_until');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paused_contacts');
    }
};
