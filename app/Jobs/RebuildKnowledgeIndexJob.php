<?php

namespace App\Jobs;

use App\Models\FaqMenu;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeSuggestion;
use App\Models\TrainingMaterial;
use App\Services\Ai\EmbeddingService;
use App\Services\Ai\Support\TextCleaner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RebuildKnowledgeIndexJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 300;

    public function handle(EmbeddingService $embedder): void
    {
        $seen    = []; // [source_type => [source_id, ...]] — anything not seen gets pruned below
        $indexed = 0;
        $skipped = 0;

        foreach ($this->collectSources() as [$sourceType, $sourceId, $content]) {
            $seen[$sourceType][] = $sourceId;

            $hash = md5($content);

            $existing = KnowledgeChunk::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->first();

            if ($existing && $existing->content_hash === $hash) {
                continue; // unchanged — skip re-embedding to save API calls
            }

            $embedding = $embedder->embed($content);
            if ($embedding === null) {
                $skipped++;
                continue; // leave any existing chunk in place; try again next rebuild
            }

            KnowledgeChunk::updateOrCreate(
                ['source_type' => $sourceType, 'source_id' => $sourceId],
                ['content' => $content, 'content_hash' => $hash, 'embedding' => $embedding]
            );
            $indexed++;
        }

        // Prune chunks whose source no longer qualifies (deleted/deactivated/rejected).
        foreach (['faq_menu', 'knowledge_suggestion', 'training_material'] as $sourceType) {
            KnowledgeChunk::where('source_type', $sourceType)
                ->whereNotIn('source_id', $seen[$sourceType] ?? [-1])
                ->delete();
        }

        Cache::forget('knowledge_chunks_index');

        Log::info('RebuildKnowledgeIndexJob: done', ['indexed' => $indexed, 'skipped' => $skipped]);
    }

    /**
     * @return iterable<array{0: string, 1: int, 2: string}> [source_type, source_id, content]
     */
    private function collectSources(): iterable
    {
        foreach (
            FaqMenu::active()
                ->whereNotNull('content')
                ->where('command', '!=', '0')
                ->get(['id', 'title', 'content']) as $item
        ) {
            $text = TextCleaner::cleanFaqContent($item->content);

            $content = '[' . $item->title . '] ' . mb_substr($text, 0, 500);

            yield ['faq_menu', $item->id, $content];
        }

        foreach (
            KnowledgeSuggestion::where('status', 'approved')
                ->where('type', 'knowledge')
                ->get(['id', 'question', 'answer']) as $item
        ) {
            $content = 'Q: ' . $item->question . ' → ' . $item->answer;

            yield ['knowledge_suggestion', $item->id, $content];
        }

        // chat_export contains raw transcripts that may include other customers' names,
        // numbers, and messages — it must never be embedded and retrievable verbatim for
        // a different customer. It's still fed to KnowledgeSynthesizerJob, which turns it
        // into curated, admin-approved Q&A (KnowledgeSuggestion) before it can reach anyone.
        foreach (
            TrainingMaterial::active()
                ->where('category', '!=', 'chat_export')
                ->get(['id', 'title', 'category', 'content']) as $item
        ) {
            $content = "[{$item->category}] {$item->title}: " . mb_substr($item->content, 0, 800);

            yield ['training_material', $item->id, $content];
        }
    }
}
