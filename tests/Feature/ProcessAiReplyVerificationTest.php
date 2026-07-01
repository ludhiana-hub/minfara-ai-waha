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

        Http::fake(function ($request) {
            $model = $request->data()['model'] ?? null;

            // Primary model (qwen/qwen3-32b, the config default) fails with a terminal (non-timeout) error.
            if ($model === 'qwen/qwen3-32b') {
                return Http::response(['error' => ['message' => 'model decommissioned']], 400);
            }

            // First fallback model in FALLBACK_MODELS['groq'] succeeds.
            if ($model === 'gemma2-9b-it') {
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
}
