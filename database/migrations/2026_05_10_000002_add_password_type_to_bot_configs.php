<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `type` ENUM('text','textarea','boolean','number','password') DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `type` ENUM('text','textarea','boolean','number') DEFAULT 'text'");
    }
};
