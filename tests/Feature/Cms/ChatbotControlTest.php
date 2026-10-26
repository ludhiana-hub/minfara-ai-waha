<?php

namespace Tests\Feature\Cms;

use App\Http\Middleware\CmsIpMiddleware;
use App\Models\BotConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CmsIpMiddleware::class);
        BotConfig::set('ai_enabled', 'true');
        BotConfig::set('faq_enabled', 'true');
    }

    private function login(): void
    {
        $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    }

    public function test_guest_cannot_change_the_switches(): void
    {
        $this->postJson(route('cms.chatbot.update'), ['ai_enabled' => false])->assertUnauthorized();

        $this->assertTrue(BotConfig::getBool('ai_enabled', true));
    }

    public function test_both_off_means_notification_only(): void
    {
        $this->login();

        $this->postJson(route('cms.chatbot.update'), ['ai_enabled' => false, 'faq_enabled' => false])
            ->assertOk()
            ->assertJson(['ai_enabled' => false, 'faq_enabled' => false, 'mode' => 'notification_only']);

        $this->assertFalse(BotConfig::getBool('ai_enabled', true));
        $this->assertFalse(BotConfig::getBool('faq_enabled', true));
    }

    public function test_toggling_one_switch_leaves_the_other_alone(): void
    {
        $this->login();

        $this->postJson(route('cms.chatbot.update'), ['ai_enabled' => false])
            ->assertOk()
            ->assertJson(['ai_enabled' => false, 'faq_enabled' => true, 'mode' => 'partial']);
    }

    public function test_turning_both_back_on_restores_full_mode(): void
    {
        $this->login();
        BotConfig::set('ai_enabled', 'false');
        BotConfig::set('faq_enabled', 'false');

        $this->postJson(route('cms.chatbot.update'), ['ai_enabled' => true, 'faq_enabled' => true])
            ->assertJson(['mode' => 'full']);
    }

    public function test_rejects_non_boolean_values(): void
    {
        $this->login();

        $this->postJson(route('cms.chatbot.update'), ['ai_enabled' => 'banana'])->assertStatus(422);
    }

    public function test_a_plain_form_post_redirects_back_with_a_flash(): void
    {
        $this->login();

        $this->from(route('cms.dashboard'))
            ->post(route('cms.chatbot.update'), ['ai_enabled' => '0', 'faq_enabled' => '0'])
            ->assertRedirect(route('cms.dashboard'))
            ->assertSessionHas('success');
    }

    public function test_dashboard_shows_the_control_card_with_both_switches(): void
    {
        $this->login();

        $this->get(route('cms.dashboard'))
            ->assertOk()
            ->assertSee('Kontrol Chatbot')
            ->assertSee('id="ctlAi"', false)
            ->assertSee('id="ctlFaq"', false);
    }

    public function test_dashboard_and_header_say_notification_only_when_both_are_off(): void
    {
        $this->login();
        BotConfig::set('ai_enabled', 'false');
        BotConfig::set('faq_enabled', 'false');

        $this->get(route('cms.dashboard'))
            ->assertOk()
            ->assertSee('Hanya Notifikasi')
            ->assertSee('data-mode="notification_only"', false);
    }

    public function test_header_badge_is_in_full_mode_by_default(): void
    {
        $this->login();

        $this->get(route('cms.dashboard'))->assertSee('data-mode="full"', false);
    }
}
