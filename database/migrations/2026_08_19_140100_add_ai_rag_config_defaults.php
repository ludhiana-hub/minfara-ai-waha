<?php

use App\Models\BotConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // ai_rag_top_k / ai_rag_min_score already exist as hardcoded fallback defaults in
        // KnowledgeRetrievalService (read via BotConfig::getInt/getFloat) but had no row in
        // the DB and no CMS UI to change them — this backfills the same defaults as real,
        // editable BotConfig rows for existing installs (BotConfigSeeder only runs on fresh
        // installs). firstOrCreate: never overwrites a value an admin may already have set
        // directly in the DB.
        BotConfig::firstOrCreate(['key' => 'ai_rag_top_k'], [
            'value'       => '5',
            'type'        => 'number',
            'label'       => 'RAG Top-K',
            'description' => 'Jumlah maksimal potongan knowledge base yang disisipkan per pesan (0-15). Isi 0 untuk menonaktifkan RAG.',
            'group'       => 'ai',
        ]);

        BotConfig::firstOrCreate(['key' => 'ai_rag_min_score'], [
            'value'       => '0.55',
            'type'        => 'number',
            'label'       => 'RAG Min Score',
            'description' => 'Ambang skor kemiripan (0.0-1.0) agar sebuah chunk dianggap relevan.',
            'group'       => 'ai',
        ]);
    }

    public function down(): void
    {
        BotConfig::where('key', 'ai_rag_top_k')->delete();
        BotConfig::where('key', 'ai_rag_min_score')->delete();
    }
};
