<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_suggestions', function (Blueprint $table) {
            $table->enum('type', ['knowledge', 'coaching'])->default('knowledge')->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_suggestions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
