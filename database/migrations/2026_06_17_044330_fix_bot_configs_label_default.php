<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make label nullable so BotConfig::set() can INSERT new config keys
        // without crashing when the key was never pre-seeded (label was NOT NULL with no default).
        // MySQL uses a raw ALTER; every other driver (SQLite in the test suite, DB_CONNECTION=sqlite
        // in phpunit.xml) goes through the schema builder, since the original create-table
        // migration declares `label` NOT NULL there too.
        if (DB::connection()->getDriverName() !== 'mysql') {
            Schema::table('bot_configs', function ($table) {
                $table->string('label')->nullable()->change();
            });
            return;
        }

        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `label` VARCHAR(255) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE bot_configs MODIFY COLUMN `label` VARCHAR(255) NOT NULL");
    }
};
