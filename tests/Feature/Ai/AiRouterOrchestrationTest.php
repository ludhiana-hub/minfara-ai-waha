<?php

namespace Tests\Feature\Ai;

use App\Models\BotConfig;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pins the orchestration behavior lifted from the pre-AiRouter ProcessAiReply loop — none
 * of this was under test before AiRouter existed. Every case here mirrors a comment/const
 * that used to live directly in ProcessAiReply.php.
 */
class AiRouterOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function router(): AiRouter
    {
        return app(AiRouter::class);
    }

    public function test_same_provider_model_fallback(): void
    {
        BotConfig::set('ai_provider_order', 'groq');
        BotConfig::set('groq_api_key', 'k');
        BotConfig::set('groq_model', 'primary-model');

        Http::fake(function ($request) {
            $model = $request->data()['model'] ?? null;

            return match ($model) {
                'primary-model'      => Http::response(['error' => ['message' => 'decommissioned']], 400),
                'openai/gpt-oss-20b' => Http::response(['choices' => [['message' => ['content' => 'ok']]], 'usage' => ['total_tokens' => 5]], 200),
                default              => Http::response(['error' => ['message' => 'unexpected model']], 500),
            };
        });

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertTrue($result->success);
        $this->assertSame('groq', $result->provider);
        $this->assertSame('openai/gpt-oss-20b', $result->model);
        $this->assertSame(2, $result->attempts);
        Http::assertSentCount(2);
    }

    public function test_cross_provider_fallback_when_provider_fully_exhausted(): void
    {
        BotConfig::set('ai_provider_order', 'groq,gemini');
        BotConfig::set('groq_api_key', 'k');
        BotConfig::set('gemini_api_key', 'k');
        BotConfig::set('groq_model', 'g-primary');
        BotConfig::set('gemini_model', 'gem-primary');

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'groq.com')) {
                return Http::response(['error' => ['message' => 'down']], 500);
            }
            // gemini
            return Http::response([
                'candidates'    => [['content' => ['parts' => [['text' => 'gemini reply']]]]],
                'usageMetadata' => ['totalTokenCount' => 7],
            ], 200);
        });

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertTrue($result->success);
        $this->assertSame('gemini', $result->provider);
        $this->assertSame('gemini reply', $result->reply);
        // groq: primary + 2 fallback models = 3 attempts, then gemini primary succeeds = 4th.
        $this->assertSame(4, $result->attempts);
    }

    public function test_attempt_cap_stops_at_exactly_max_total_attempts(): void
    {
        BotConfig::set('ai_provider_order', 'groq,gemini,openrouter,nvidia');
        foreach (['groq', 'gemini', 'openrouter', 'nvidia'] as $p) {
            BotConfig::set("{$p}_api_key", 'k');
        }

        // groq (3 models) + gemini (2 models) + openrouter (1) + nvidia (1) = 7 possible
        // attempts — pin the cap locally to 6 (independent of whatever production's default
        // is) so this test keeps exercising the cap-stops-mid-chain mechanism itself.
        config(['ai_profiles.defaults.max_total_attempts' => 6]);
        Http::fake(fn () => Http::response(['error' => ['message' => 'down']], 500));

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertFalse($result->success);
        $this->assertSame(6, $result->attempts);
        Http::assertSentCount(6);
    }

    public function test_provider_marked_unhealthy_after_full_exhaustion(): void
    {
        BotConfig::set('ai_provider_order', 'groq');
        BotConfig::set('groq_api_key', 'k');

        Http::fake(fn () => Http::response(['error' => ['message' => 'down']], 500));

        $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertTrue(Cache::has('ai_provider_unhealthy_groq'));
    }

    public function test_unhealthy_provider_is_skipped_on_next_call(): void
    {
        Cache::put('ai_provider_unhealthy_groq', true, 300);

        BotConfig::set('ai_provider_order', 'groq,gemini');
        BotConfig::set('groq_api_key', 'k');
        BotConfig::set('gemini_api_key', 'k');

        Http::fake(fn () => Http::response([
            'candidates'    => [['content' => ['parts' => [['text' => 'gemini reply']]]]],
            'usageMetadata' => ['totalTokenCount' => 3],
        ], 200));

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertSame('gemini', $result->provider);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'groq.com'));
    }

    public function test_fail_open_ignores_marks_when_every_provider_is_unhealthy(): void
    {
        BotConfig::set('ai_provider_order', 'groq,gemini');
        BotConfig::set('groq_api_key', 'k');
        BotConfig::set('gemini_api_key', 'k');

        Cache::put('ai_provider_unhealthy_groq', true, 300);
        Cache::put('ai_provider_unhealthy_gemini', true, 300);

        Http::fake(fn () => Http::response(['choices' => [['message' => ['content' => 'ok']]], 'usage' => ['total_tokens' => 1]], 200));

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        // Both marked unhealthy, but since that's ALL providers, the marks are ignored
        // entirely and the normal order runs anyway — never skip straight to failure.
        $this->assertTrue($result->success);
        $this->assertSame('groq', $result->provider);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'groq.com'));
    }

    public function test_attempt_cap_cutting_a_provider_short_does_not_mark_it_unhealthy(): void
    {
        BotConfig::set('ai_provider_order', 'gemini,openrouter,nvidia,groq');
        foreach (['gemini', 'openrouter', 'nvidia', 'groq'] as $p) {
            BotConfig::set("{$p}_api_key", 'k');
        }

        // Pin the cap locally to 6 (independent of production's default) — see the sibling
        // test above for why.
        config(['ai_profiles.defaults.max_total_attempts' => 6]);
        Http::fake(fn () => Http::response(['error' => ['message' => 'down']], 500));

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        // gemini (2 models) + openrouter (1) + nvidia (1) = 4 attempts fully consumed
        // (all genuinely exhausted → SHOULD be marked unhealthy). That leaves 2 of the
        // 6-attempt budget for groq's 3 models — cut short, so groq must NOT be marked.
        $this->assertFalse($result->success);
        $this->assertSame(6, $result->attempts);
        $this->assertTrue(Cache::has('ai_provider_unhealthy_gemini'));
        $this->assertTrue(Cache::has('ai_provider_unhealthy_openrouter'));
        $this->assertTrue(Cache::has('ai_provider_unhealthy_nvidia'));
        $this->assertFalse(Cache::has('ai_provider_unhealthy_groq'));
    }

    public function test_connection_timeout_retries_once_then_gives_up(): void
    {
        // openrouter has no fallback models in the chat profile — isolates the single-model
        // retry behavior cleanly (2 attempts: original + 1 retry, same model).
        BotConfig::set('ai_provider_order', 'openrouter');
        BotConfig::set('openrouter_api_key', 'k');

        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertFalse($result->success);
        // Http::fake()'s request recorder doesn't log calls whose closure throws before
        // completing, so $result->attempts (AiRouter's own counter, incremented on every
        // real attempt() call regardless of outcome) is the reliable signal here.
        $this->assertSame(2, $result->attempts);
        $this->assertSame('Connection timeout', $result->lastError());
    }

    public function test_quota_exceeded_is_not_retried(): void
    {
        BotConfig::set('ai_provider_order', 'openrouter');
        BotConfig::set('openrouter_api_key', 'k');

        Http::fake(fn () => Http::response(['error' => ['message' => 'rate limited']], 429));

        $result = $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertFalse($result->success);
        $this->assertSame(1, $result->attempts);
        Http::assertSentCount(1);
    }

    public function test_eval_profile_exhaustion_never_touches_the_chat_circuit_breaker_key(): void
    {
        BotConfig::set('ai_provider_order', 'groq');
        BotConfig::set('groq_api_key', 'k');

        Http::fake(fn () => Http::response(['error' => ['message' => 'down']], 500));

        $this->router()->run(AiRequest::make('eval', 'hi'));

        // 'eval' profile has mark_unhealthy=false AND a distinct circuit_scope — the
        // unscoped chat key must stay completely untouched either way.
        $this->assertFalse(Cache::has('ai_provider_unhealthy_groq'));
    }

    public function test_expecting_json_with_unparseable_reply_falls_through_to_next_model(): void
    {
        BotConfig::set('ai_provider_order', 'openrouter,gemini');
        BotConfig::set('openrouter_api_key', 'k');
        BotConfig::set('gemini_api_key', 'k');

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'openrouter.ai')) {
                return Http::response(['choices' => [['message' => ['content' => 'Sorry, I cannot help with that.']]], 'usage' => ['total_tokens' => 4]], 200);
            }

            return Http::response([
                'candidates'    => [['content' => ['parts' => [['text' => '{"ok": true}']]]]],
                'usageMetadata' => ['totalTokenCount' => 6],
            ], 200);
        });

        $result = $this->router()->run(AiRequest::make('chat', 'hi')->expectingJson());

        $this->assertTrue($result->success);
        $this->assertSame('gemini', $result->provider);
        $this->assertSame(['ok' => true], $result->json);
        $this->assertSame('json_parse_failed', $result->attemptLog['openrouter'][array_key_first($result->attemptLog['openrouter'])]);
    }

    public function test_reasoning_effort_is_sent_only_for_gpt_oss_models(): void
    {
        BotConfig::set('ai_provider_order', 'groq');
        BotConfig::set('groq_api_key', 'k');
        BotConfig::set('groq_model', 'openai/gpt-oss-120b');

        $payloads = [];
        Http::fake(function ($request) use (&$payloads) {
            $payloads[] = $request->data();
            $model = $request->data()['model'] ?? null;

            return match ($model) {
                'openai/gpt-oss-120b'      => Http::response(['error' => ['message' => 'decommissioned']], 400),
                'openai/gpt-oss-20b'       => Http::response(['error' => ['message' => 'decommissioned']], 400),
                'llama-3.3-70b-versatile'  => Http::response(['choices' => [['message' => ['content' => 'ok']]], 'usage' => ['total_tokens' => 5]], 200),
                default                    => Http::response(['error' => ['message' => 'unexpected model']], 500),
            };
        });

        $this->router()->run(AiRequest::make('chat', 'hi'));

        $this->assertCount(3, $payloads);
        $this->assertArrayHasKey('reasoning_effort', $payloads[0]);
        $this->assertSame('none', $payloads[0]['reasoning_effort']);
        $this->assertArrayHasKey('reasoning_effort', $payloads[1]);
        $this->assertArrayNotHasKey('reasoning_effort', $payloads[2]);
    }
}
