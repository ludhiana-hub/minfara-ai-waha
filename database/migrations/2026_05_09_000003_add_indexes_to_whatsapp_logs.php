<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Index untuk dari number query
            $table->index('from_number');
            // Index untuk created_at untuk analytics
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropIndex(['from_number']);
            $table->dropIndex(['created_at']);
        });
    }
};
