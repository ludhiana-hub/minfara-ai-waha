<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiReply;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Services\Ai\AiRouter;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatbotSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        FaqMenu::create([
            'command'   => '1',
            'title'     => 'Jam Buka',
            'content'   => 'Jam buka 08.00-20.00',
            'is_active' => true,
        ]);
    }

    private function setSwitches(bool $ai, bool $faq): void
    {
        BotConfig::set('ai_enabled', $ai ? 'true' : 'false');
        BotConfig::set('faq_enabled', $faq ? 'true' : 'false');
    }

    private function mockWhatsapp(): \Mockery\MockInterface
    {
        return $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('startTyping')->andReturn(null);
            $mock->shouldReceive('stopTyping')->andReturn(null);
        });
    }

    private function incoming(string $body, string $id): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'event'   => 'message',
            'payload' => ['from' => '6281234567890@c.us', 'body' => $body, 'fromMe' => false, 'id' => $id],
        ])->assertOk();
    }

    public function test_both_off_drops_faq_command_silently(): void
    {
        $this->setSwitches(false, false);
        $this->mockWhatsapp()->shouldNotReceive('sendMessage');
        Queue::fake();

        $this->incoming('1', 'M1');

        Queue::assertNotPushed(ProcessAiReply::class);
    }

    public function test_both_off_drops_free_text_silently(): void
    {
        $this->setSwitches(false, false);
        $this->mockWhatsapp()->shouldNotReceive('sendMessage');
        Queue::fake();

        $this->incoming('mau tanya produk', 'M2');

        Queue::assertNotPushed(ProcessAiReply::class);
    }

    public function test_faq_off_routes_faq_command_to_ai(): void
    {
        $this->setSwitches(true, false);
        $this->mockWhatsapp()->shouldNotReceive('sendMessage');
        Queue::fake();

        $this->incoming('1', 'M3');

        Queue::assertPushed(ProcessAiReply::class);
    }

    public function test_ai_off_faq_on_still_answers_faq(): void
    {
        $this->setSwitches(false, true);
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->andReturn(true);
        Queue::fake();

        $this->incoming('1', 'M4');

        Queue::assertNotPushed(ProcessAiReply::class);
    }

    public function test_queued_ai_job_does_nothing_when_both_off(): void
    {
        $this->setSwitches(false, false);
        $whatsapp = $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('isSessionWorking')->andReturn(true);
            $mock->shouldNotReceive('sendMessage');
        });

        (new ProcessAiReply('6281234567890@c.us', '6281234567890', 'halo apa kabar', null, null))
            ->handle($whatsapp, app(AiRouter::class));

        $this->addToAssertionCount(1);
    }

    public function test_notification_job_still_sends_when_both_off(): void
    {
        $this->setSwitches(false, false);
        $whatsapp = $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('isSessionWorking')->andReturn(true);
            $mock->shouldReceive('sendMessage')->once()->with('628111111111', 'Pesanan baru')->andReturn(true);
        });

        (new SendWhatsAppNotificationJob('628111111111', 'Pesanan baru'))->handle($whatsapp);
    }
}
