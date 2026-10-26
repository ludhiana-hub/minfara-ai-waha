<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiReply;
use App\Models\BotConfig;
use App\Models\LeadProfile;
use App\Services\Sales\SalesMemory;
use App\Services\Sales\SalesPlaybook;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesMemoryTest extends TestCase
{
    use RefreshDatabase;

    private const FROM = '6281234567890';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        BotConfig::set('ai_enabled', 'true');
        BotConfig::set('ai_provider_order', 'groq');
        BotConfig::set('groq_api_key', 'test-groq-key');
        BotConfig::set('footer_ai', '');
    }

    private function observe(string $message): LeadProfile
    {
        return app(SalesMemory::class)->observe(self::FROM, 'Budi', $message);
    }

    public function test_facts_accumulate_across_messages(): void
    {
        $this->observe('mau belajar bahasa jerman');
        $profile = $this->observe('buat kerja, dari nol');

        $this->assertSame('Jerman', $profile->language);
        $this->assertSame('kerja', $profile->goal);
        $this->assertSame('pemula', $profile->level);
        $this->assertSame(1, LeadProfile::count());
    }

    public function test_a_later_message_does_not_erase_earlier_facts(): void
    {
        $this->observe('bahasa jerman level A1');
        $profile = $this->observe('jam buka admin kapan?');

        $this->assertSame('Jerman', $profile->language);
        $this->assertSame('A1', $profile->level);
    }

    public function test_stage_progresses_from_new_to_qualified(): void
    {
        $this->assertSame('new', $this->observe('halo')->stage);
        $this->assertSame('exploring', $this->observe('info bahasa jerman dong')->stage);
        $this->assertSame('qualified', $this->observe('buat kerja, lifetime berapa?')->stage);
    }

    public function test_objection_takes_priority_and_is_remembered(): void
    {
        $this->observe('bahasa jerman buat kerja, lifetime berapa?');
        $profile = $this->observe('wah mahal ya');

        $this->assertSame('objection', $profile->stage);
        $this->assertContains('harga', $profile->objections);
    }

    public function test_objection_stage_is_not_sticky(): void
    {
        $this->observe('bahasa jerman buat kerja, lifetime berapa?');
        $this->observe('wah mahal ya');

        $this->assertSame('qualified', $this->observe('oh gitu, bisa dipakai di HP juga?')->stage);
    }

    public function test_buying_intent_moves_to_ready_then_checkout_sent_and_stays_there(): void
    {
        $memory = app(SalesMemory::class);

        $profile = $this->observe('oke mau daftar, gimana caranya?');
        $this->assertSame('ready_to_checkout', $profile->stage);

        $memory->afterReply($profile, 'Yuk checkout di https://mitfara.com ya kak');
        $this->assertSame('checkout_sent', $profile->fresh()->stage);
        $this->assertNotNull($profile->fresh()->checkout_pushed_at);

        // Next turn has no buying signal — must not fall back to qualified/exploring and re-pitch.
        $this->assertSame('checkout_sent', $this->observe('oke makasih kak')->stage);
    }

    public function test_playbook_for_checkout_sent_does_not_repeat_the_link(): void
    {
        $profile = new LeadProfile(['language' => 'Jerman', 'stage' => 'checkout_sent']);

        $block = SalesPlaybook::block($profile);

        $this->assertStringContainsString('JANGAN tempel ulang', $block);
        $this->assertStringContainsString('Bahasa: Jerman', $block);
    }

    public function test_playbook_for_ready_gives_concrete_checkout_steps_on_the_web(): void
    {
        $block = SalesPlaybook::block(new LeadProfile(['stage' => 'ready_to_checkout']));

        $this->assertStringContainsString('https://mitfara.com', $block);
        $this->assertStringContainsString('Katalog Kursus', $block);
        $this->assertStringContainsString('Beli Sekarang', $block);
        // Manual bank transfer is a fallback only, never offered first.
        $this->assertStringContainsString('transfer manual', $block);
        $this->assertStringContainsString('hanya jika', $block);
    }

    public function test_playbook_does_not_ask_again_for_what_is_already_known(): void
    {
        $block = SalesPlaybook::block(new LeadProfile(['language' => 'Jerman', 'level' => 'A1', 'stage' => 'exploring']));

        $this->assertStringContainsString('tujuan', $block);       // still missing → ask
        $this->assertStringNotContainsString('tanyakan level', $block); // known → don't ask
    }

    public function test_profiles_older_than_90_days_are_prunable(): void
    {
        $old = LeadProfile::create(['from_number' => '111', 'stage' => 'new']);
        $old->forceFill(['updated_at' => now()->subDays(91)])->saveQuietly();
        LeadProfile::create(['from_number' => '222', 'stage' => 'new']);

        $this->assertSame(1, (new LeadProfile())->pruneAll());
        $this->assertSame(['222'], LeadProfile::pluck('from_number')->all());
    }

    private function mockWhatsapp(): \Mockery\MockInterface
    {
        return $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('isSessionWorking')->andReturn(true);
            $mock->shouldReceive('startTyping')->andReturn(null);
            $mock->shouldReceive('stopTyping')->andReturn(null);
        });
    }

    private function fakeAi(string $reply = 'Siap kak!'): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => $reply], 'finish_reason' => 'stop']],
                'usage'   => ['total_tokens' => 10],
            ], 200),
        ]);
    }

    private function systemPromptSent(): string
    {
        $found = '';
        Http::assertSent(function ($request) use (&$found) {
            if (!str_contains($request->url(), 'api.groq.com')) {
                return false;
            }
            $found = collect($request->data()['messages'])->firstWhere('role', 'system')['content'] ?? '';
            return true;
        });

        return $found;
    }

    public function test_reply_prompt_carries_the_lead_context(): void
    {
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->andReturn(true);
        $this->fakeAi();

        ProcessAiReply::dispatchSync('6281234567890@c.us', self::FROM, 'mau belajar bahasa jerman buat kerja', 'Budi', null);

        $prompt = $this->systemPromptSent();
        $this->assertStringContainsString('KONTEKS SALES', $prompt);
        $this->assertStringContainsString('Bahasa: Jerman', $prompt);
        $this->assertStringContainsString('Tujuan: kerja', $prompt);
    }

    public function test_context_survives_into_a_later_message_that_mentions_nothing(): void
    {
        $this->mockWhatsapp()->shouldReceive('sendMessage')->atLeast()->once()->andReturn(true);
        $this->fakeAi();

        ProcessAiReply::dispatchSync('6281234567890@c.us', self::FROM, 'mau belajar bahasa jerman buat kerja', 'Budi', null);
        Cache::forget('ai_cooldown_' . md5(self::FROM));
        ProcessAiReply::dispatchSync('6281234567890@c.us', self::FROM, 'bisa dipakai di HP ga?', 'Budi', null);

        $this->assertStringContainsString('Bahasa: Jerman', $this->systemPromptSent());
    }

    public function test_switch_off_leaves_the_prompt_untouched(): void
    {
        BotConfig::set('ai_sales_memory_enabled', 'false');
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->andReturn(true);
        $this->fakeAi();

        ProcessAiReply::dispatchSync('6281234567890@c.us', self::FROM, 'mau belajar bahasa jerman', 'Budi', null);

        $this->assertStringNotContainsString('KONTEKS SALES', $this->systemPromptSent());
        $this->assertSame(0, LeadProfile::count());
    }

    public function test_a_broken_memory_store_never_blocks_the_reply(): void
    {
        Schema::drop('lead_profiles');
        $sent = null;
        $this->mockWhatsapp()->shouldReceive('sendMessage')->once()->withArgs(function ($_c, $text) use (&$sent) {
            $sent = $text;
            return true;
        })->andReturn(true);
        $this->fakeAi('Balasan normal');

        ProcessAiReply::dispatchSync('6281234567890@c.us', self::FROM, 'mau belajar bahasa jerman', 'Budi', null);

        $this->assertSame('Balasan normal', $sent);
    }
}
