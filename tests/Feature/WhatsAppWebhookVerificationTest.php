<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiReply;
use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\PausedContact;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function mockWhatsapp(): \Mockery\MockInterface
    {
        return $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('startTyping')->andReturn(null);
            $mock->shouldReceive('stopTyping')->andReturn(null);
        });
    }

    public function test_faq_command_answers_directly_without_dispatching_ai(): void
    {
        FaqMenu::create([
            'command'   => '1',
            'title'     => 'Jam Buka',
            'content'   => 'Jam buka kami Senin-Sabtu 08.00-20.00 WIB',
            'is_active' => true,
        ]);

        $sentReply = null;
        $this->mockWhatsapp()
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($_chatId, $text) use (&$sentReply) {
                $sentReply = $text;
                return true;
            })
            ->andReturn(true);

        Queue::fake();

        $response = $this->postJson('/api/whatsapp/webhook', [
            'event'   => 'message',
            'payload' => [
                'from'   => '6281234567890@c.us',
                'body'   => '1',
                'fromMe' => false,
                'id'     => 'MSG_FAQ_1',
            ],
        ]);

        $response->assertOk();
        $this->assertSame('Jam buka kami Senin-Sabtu 08.00-20.00 WIB', $sentReply);
        Queue::assertNotPushed(ProcessAiReply::class);
    }

    public function test_free_text_message_dispatches_generative_ai_job(): void
    {
        $this->mockWhatsapp();
        Queue::fake();

        $response = $this->postJson('/api/whatsapp/webhook', [
            'event'   => 'message',
            'payload' => [
                'from'   => '6281234567890@c.us',
                'body'   => 'saya mau tanya soal produk kalian dong',
                'fromMe' => false,
                'id'     => 'MSG_FREE_1',
            ],
        ]);

        $response->assertOk();
        Queue::assertPushed(ProcessAiReply::class);
    }

    public function test_owner_reply_sets_takeover_pause_for_configured_duration_and_schedules_resume(): void
    {
        BotConfig::set('human_takeover_minutes', '7');

        $from     = '6281234567890';
        $fromHash = md5($from);

        // Mimic webhook()'s own bookkeeping from a prior customer message (lines 199-204),
        // required for the proactive-resume branch to have something to resume with.
        Cache::put('pending_resume_chatid_' . $fromHash, $from . '@c.us', now()->addHours(2));
        Cache::put('pending_resume_body_' . $fromHash, 'pesan terakhir customer', now()->addHours(2));
        Cache::put('pending_resume_name_' . $fromHash, 'Budi', now()->addHours(2));

        $this->mockWhatsapp();
        Queue::fake();

        $response = $this->postJson('/api/whatsapp/webhook', [
            'event'   => 'message.any',
            'payload' => [
                'from'   => 'BOT_OWN_NUMBER@c.us',
                'to'     => $from . '@c.us',
                'body'   => 'oke kak, sudah saya bantu ya',
                'fromMe' => true,
                'id'     => 'MSG_OWNER_1',
            ],
        ]);

        $response->assertOk();

        $this->assertTrue(Cache::has('human_takeover_' . $fromHash));
        $this->assertTrue(PausedContact::isPaused($from));

        $paused = PausedContact::where('from_number', $from)->first();
        $this->assertNotNull($paused);
        $this->assertEqualsWithDelta(
            now()->addMinutes(7)->timestamp,
            $paused->paused_until->timestamp,
            5,
            'paused_until should reflect human_takeover_minutes (7), not a hardcoded value'
        );

        Queue::assertPushed(ProcessAiReply::class, function (ProcessAiReply $job) {
            $ref  = new \ReflectionClass($job);
            $prop = $ref->getProperty('from');
            $prop->setAccessible(true);
            $from = $prop->getValue($job);

            if ($job->delay === null) {
                return false;
            }
            $delayTimestamp = \Illuminate\Support\Carbon::parse($job->delay)->timestamp;

            return $from === '6281234567890'
                && abs($delayTimestamp - now()->addMinutes(7)->addSeconds(5)->timestamp) < 5;
        });
    }
}
