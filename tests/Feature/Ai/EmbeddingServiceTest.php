<?php

namespace Tests\Feature\Ai;

use App\Jobs\RebuildKnowledgeIndexJob;
use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\KnowledgeChunk;
use App\Services\Ai\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        BotConfig::set('gemini_api_key', 'test-gemini-key');
    }

    private function embedCalls(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), ':embedContent'))->count();
    }

    public function test_uses_a_live_embedding_model_with_the_key_in_a_header(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['embedding' => ['values' => [0.1, 0.2]]], 200)]);

        $values = app(EmbeddingService::class)->embed('halo');

        $this->assertSame([0.1, 0.2], $values);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'models/gemini-embedding-001:embedContent')
                && !str_contains($request->url(), 'key=')
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && ($request->data()['outputDimensionality'] ?? null) === 768;
        });
    }

    public function test_a_failing_embedding_endpoint_is_not_hit_again_on_every_message(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'not found']], 404)]);
        $embedder = app(EmbeddingService::class);

        $this->assertNull($embedder->embed('pesan satu'));
        $this->assertNull($embedder->embed('pesan dua'));
        $this->assertNull($embedder->embed('pesan tiga'));

        // One probe, then the breaker holds — otherwise every customer message burns a Gemini request.
        $this->assertSame(1, $this->embedCalls());
    }

    public function test_recovers_after_the_cooldown_expires(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'x']], 500)
            ->push(['embedding' => ['values' => [1.0]]], 200)]);
        $embedder = app(EmbeddingService::class);

        $this->assertNull($embedder->embed('a'));
        $this->assertNull($embedder->embed('b')); // still inside the cooldown

        Cache::forget('embedding_backoff');

        $this->assertEquals([1.0], $embedder->embed('b'));
    }

    public function test_index_rebuild_re_embeds_chunks_made_by_a_previous_model(): void
    {
        $menu = FaqMenu::create(['command' => '1', 'title' => 'Jam Buka', 'content' => 'Senin-Sabtu 08.00-20.00', 'is_active' => true]);

        // Row exactly as the old job wrote it: hash of the bare content, old-model vector.
        $content = '[Jam Buka] Senin-Sabtu 08.00-20.00';
        KnowledgeChunk::create([
            'source_type'  => 'faq_menu',
            'source_id'    => $menu->id,
            'content'      => $content,
            'content_hash' => md5($content),
            'embedding'    => [0.5, 0.5],
        ]);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['embedding' => ['values' => [0.9, 0.8, 0.7]]], 200)]);

        (new RebuildKnowledgeIndexJob())->handle(app(EmbeddingService::class));

        $this->assertSame([0.9, 0.8, 0.7], KnowledgeChunk::where('source_id', $menu->id)->first()->embedding);
    }
}
