<?php

use App\Models\BotConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Groq decommissioned qwen/qwen3-32b on 2026-07-17. Seeder's firstOrCreate() never
        // overwrites an existing row, so every already-deployed install is stuck on the dead
        // model until this runs. Guarded by ->where('value', ...) so it's a no-op if an admin
        // already changed it via CMS.
        BotConfig::where('key', 'groq_model')
            ->where('value', 'qwen/qwen3-32b')
            ->update(['value' => 'openai/gpt-oss-120b']);
    }

    public function down(): void
    {
        BotConfig::where('key', 'groq_model')
            ->where('value', 'openai/gpt-oss-120b')
            ->update(['value' => 'qwen/qwen3-32b']);
    }
};
