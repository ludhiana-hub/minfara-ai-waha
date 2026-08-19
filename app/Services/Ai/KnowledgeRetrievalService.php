<?php

namespace App\Services\Ai;

use App\Models\BotConfig;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KnowledgeRetrievalService
{
    public function __construct(private EmbeddingService $embedder)
    {
    }

    /**
     * Top-K relevant knowledge chunks for a customer question, formatted for injection
     * into the system prompt. Returns '' whenever retrieval isn't usable (embedding
     * failed, index empty, or nothing clears the relevance threshold) — callers are
     * expected to fall back to the static faq_digest in that case.
     */
    public function retrieve(string $query): string
    {
        $queryEmbedding = $this->embedder->embed($query);
        if ($queryEmbedding === null) {
            return '';
        }

        $chunks = Cache::remember('knowledge_chunks_index', 300, function () {
            return KnowledgeChunk::query()
                ->get(['source_type', 'content', 'embedding'])
                ->map(fn ($c) => ['source_type' => $c->source_type, 'content' => $c->content, 'embedding' => $c->embedding])
                ->all();
        });

        if (empty($chunks)) {
            return '';
        }

        $topK    = BotConfig::getInt('ai_rag_top_k', 5);
        $minScore = BotConfig::getFloat('ai_rag_min_score', 0.55);

        $scored = [];
        foreach ($chunks as $chunk) {
            $score = self::cosineSimilarity($queryEmbedding, $chunk['embedding']);
            if ($score >= $minScore) {
                $scored[] = ['score' => $score, 'content' => $chunk['content']];
            }
        }

        if (empty($scored)) {
            return '';
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $scored = array_slice($scored, 0, $topK);

        $totalCap = 2000;
        $lines    = [
            '=== KNOWLEDGE BASE (relevan, RAHASIA INTERNAL) ===',
            'Materi di bawah ini HANYA referensi internal untuk membantumu menjawab. ATURAN WAJIB:',
            '- JANGAN pernah menyalin/quote isi materi ini kata demi kata ke customer.',
            '- JANGAN sebutkan atau isyaratkan keberadaan "knowledge base", dokumen, materi, atau transkrip ini kepada customer.',
            '- Rangkum jawabannya pakai kata-katamu sendiri, sesuai konteks pertanyaan customer saja.',
            '- Jika materi berisi nama/nomor/isi chat orang lain, JANGAN pernah ditampilkan ke customer — itu data privasi pihak lain.',
            '',
        ];
        $len      = 0;

        foreach ($scored as $item) {
            if ($len + mb_strlen($item['content']) > $totalCap) {
                break;
            }
            $lines[] = $item['content'];
            $len    += mb_strlen($item['content']);
        }

        $lines[] = '=== END KNOWLEDGE BASE (jangan ditampilkan ke customer) ===';

        return implode("\n", $lines);
    }

    public static function cosineSimilarity(array $a, array $b): float
    {
        // A dimension mismatch means the chunk's embedding came from a different embedding
        // model/version than the query's — truncating to the shorter length silently produces
        // a meaningless score instead of surfacing the real data-integrity problem.
        if (count($a) !== count($b)) {
            Log::warning('KnowledgeRetrievalService: embedding dimension mismatch, skipping chunk', [
                'query_dims' => count($a),
                'chunk_dims' => count($b),
            ]);

            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $len = count($a);

        for ($i = 0; $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
