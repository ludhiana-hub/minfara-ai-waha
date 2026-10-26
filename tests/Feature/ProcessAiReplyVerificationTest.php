<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiReply;
use App\Models\BotConfig;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessAiReplyVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        BotConfig::set('ai_enabled', 'true');
        BotConfig::set('ai_provider_order', 'groq');
        BotConfig::set('groq_api_key', 'test-groq-key');
        BotConfig::set('fallback_message', 'FALLBACK_MESSAGE_MARKER');
    }

    private function mockWhatsapp(): \Mockery\MockInterface
    {
        return $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('isSessionWorking')->andReturn(true);
            $mock->shouldReceive('startTyping')->andReturn(null);
            $mock->shouldReceive('stopTyping')->andReturn(null);
        });
    }

    public function test_generative_ai_answers_and_faq_digest_is_grounded_in_prompt(): void
    {
        BotConfig::set('faq_digest', 'Jam buka: Senin-Sabtu 08.00-20.00 WIB');

        $sentReply = null;
        $this->mockWhatsapp()
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) use (&$sentReply) {
                $sentReply = $text;
                return true;
            })
            ->andReturn(true);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Jam buka kami 08.00-20.00 ya kak!']]],
                'usage'   => ['total_tokens' => 42],
            ], 200),
        ]);

        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'jam buka jam berapa?', 'Budi', '127.0.0.1');

        $this->assertSame('Jam buka kami 08.00-20.00 ya kak!', $sentReply);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $systemMessage = collect($body['messages'])->firstWhere('role', 'system');
            return $systemMessage && str_contains($systemMessage['content'], 'Jam buka: Senin-Sabtu 08.00-20.00 WIB');
        });
    }

    public function test_falls_back_to_secondary_model_on_same_provider_when_primary_model_fails(): void
    {
        $this->mockWhatsapp()
            ->shouldReceive('sendMessage')
            ->once()
            ->with(\Mockery::any(), 'Balasan dari model cadangan')
            ->andReturn(true);

        BotConfig::set('groq_model', 'openai/gpt-oss-120b');

        Http::fake(function ($request) {
            $model = $request->data()['model'] ?? null;

            // Primary model (openai/gpt-oss-120b, the BotConfig value) fails with a terminal (non-timeout) error.
            if ($model === 'openai/gpt-oss-120b') {
                return Http::response(['error' => ['message' => 'model decommissioned']], 400);
            }

            // First fallback model in FALLBACK_MODELS['groq'] succeeds.
            if ($model === 'openai/gpt-oss-20b') {
                return Http::response([
                    'choices' => [['message' => ['content' => 'Balasan dari model cadangan']]],
                    'usage'   => ['total_tokens' => 10],
                ], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected model in test']], 500);
        });

        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'halo, ada promo?', 'Budi', '127.0.0.1');

        Http::assertSentCount(2);
    }

    public function test_human_takeover_blocks_ai_reply_entirely(): void
    {
        $from = '6281234567890';
        Cache::put('human_takeover_' . md5($from), true, 600);

        $this->mockWhatsapp()->shouldNotReceive('sendMessage');

        Http::fake();

        ProcessAiReply::dispatchSync($from . '@c.us', $from, 'halo min', 'Budi', '127.0.0.1');

        Http::assertNothingSent();
    }

    public function test_all_providers_exhausted_sends_fallback_message(): void
    {
        $sentReply = null;
        $this->mockWhatsapp()
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) use (&$sentReply) {
                $sentReply = $text;
                return true;
            })
            ->andReturn(true);

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => ['message' => 'server error']], 500),
        ]);

        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'halo', 'Budi', '127.0.0.1');

        $this->assertStringContainsString('FALLBACK_MESSAGE_MARKER', $sentReply);
    }

    public function test_short_message_gets_reduced_max_tokens(): void
    {
        BotConfig::set('ai_max_tokens', '500');
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->andReturn(true);

        $sentMaxTokens = null;
        Http::fake(function ($request) use (&$sentMaxTokens) {
            $sentMaxTokens = $request->data()['max_tokens'] ?? null;
            return Http::response(['choices' => [['message' => ['content' => 'ok']]], 'usage' => ['total_tokens' => 3]], 200);
        });

        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'harga?', 'Budi', '127.0.0.1');

        $this->assertSame(220, $sentMaxTokens);
    }

    public function test_long_detailed_message_keeps_full_max_tokens(): void
    {
        BotConfig::set('ai_max_tokens', '500');
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->andReturn(true);

        $sentMaxTokens = null;
        Http::fake(function ($request) use (&$sentMaxTokens) {
            $sentMaxTokens = $request->data()['max_tokens'] ?? null;
            return Http::response(['choices' => [['message' => ['content' => 'ok']]], 'usage' => ['total_tokens' => 3]], 200);
        });

        ProcessAiReply::dispatchSync(
            '6281234567890@c.us',
            '6281234567890',
            'jelaskan detail perbedaan paket Lifetime sama bulanan dong, kenapa lifetime lebih baik?',
            'Budi',
            '127.0.0.1'
        );

        // A detail question gets headroom above the flat ai_max_tokens, matching LONG_REPLY_HINT
        // which tells the model it may write more.
        $this->assertSame(900, $sentMaxTokens);
    }

    private function chatRequestCount(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), 'api.groq.com'))->count();
    }

    public function test_reply_cut_off_at_token_limit_is_trimmed_to_a_complete_sentence(): void
    {
        $sentReply = null;
        $this->mockWhatsapp()
            ->shouldReceive('sendMessage')
            ->atLeast()->once() // 2nd send may be skipped by the 3s duplicate guard
            ->withArgs(function ($_chatId, $text) use (&$sentReply) {
                $sentReply = $text;
                return true;
            })
            ->andReturn(true);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500), // RAG embeddings: keep the test hermetic
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message'       => ['content' => 'Paket A harga 100rb. Paket B harga 200rb dan terma'],
                    'finish_reason' => 'length',
                ]],
                'usage' => ['total_tokens' => 500],
            ], 200),
        ]);

        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'paket apa aja?', 'Budi', '127.0.0.1');

        $this->assertStringContainsString('Paket A harga 100rb.', $sentReply);
        $this->assertStringNotContainsString('terma', $sentReply);
        // Trimming is local — it must not spend another provider request (RPM).
        $this->assertSame(1, $this->chatRequestCount());

        // Same question again with a clean slate: a truncated reply must not have been put in
        // the response cache, so the provider is asked again instead of replaying the cut-off text.
        Cache::forget('chat_history_' . md5('6281234567890'));
        Cache::forget('ai_cooldown_' . md5('6281234567890'));
        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'paket apa aja?', 'Budi', '127.0.0.1');
        $this->assertSame(2, $this->chatRequestCount());
    }

    public function test_stored_history_never_ends_mid_sentence(): void
    {
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->andReturn(true);

        $long = str_repeat('Ini kalimat lengkap yang cukup panjang. ', 12);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => $long], 'finish_reason' => 'stop']],
                'usage'   => ['total_tokens' => 50],
            ], 200),
        ]);

        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'ceritain dong', 'Budi', '127.0.0.1');

        $history = Cache::get('chat_history_' . md5('6281234567890'));
        $assistant = collect($history)->firstWhere('role', 'assistant')['content'];
        $this->assertStringEndsWith('.', $assistant);
    }

    public function test_gemini_25_flash_disables_thinking_so_it_cannot_eat_the_output_budget(): void
    {
        BotConfig::set('ai_provider_order', 'gemini');
        BotConfig::set('gemini_api_key', 'test-gemini-key');
        BotConfig::set('gemini_model', 'gemini-2.5-flash');
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->andReturn(true);

        $sent = null;
        Http::fake(function ($request) use (&$sent) {
            $sent = $request->data()['generationConfig'] ?? null;
            return Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'ok']]], 'finishReason' => 'STOP']],
                'usageMetadata' => ['totalTokenCount' => 5],
            ], 200);
        });

        ProcessAiReply::dispatchSync('6281234567890@c.us', '6281234567890', 'halo kak apa kabar hari ini', 'Budi', '127.0.0.1');

        $this->assertSame(0, $sent['thinkingConfig']['thinkingBudget'] ?? null);
    }

    public function test_failed_does_not_send_fallback_when_human_takeover_active(): void
    {
        $from = '6281234567890';
        Cache::put('human_takeover_' . md5($from), true, 600);

        $job = new ProcessAiReply($from . '@c.us', $from, 'halo min', 'Budi', '127.0.0.1');

        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldNotReceive('sendMessage');
        });

        $job->failed(new \RuntimeException('simulated permanent failure'));
    }
}
