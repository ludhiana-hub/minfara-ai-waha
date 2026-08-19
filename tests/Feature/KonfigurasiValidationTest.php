<?php

namespace Tests\Feature;

use App\Http\Middleware\CmsIpMiddleware;
use App\Models\BotConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonfigurasiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CmsIpMiddleware::class);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_ai_max_tokens_above_bound_is_rejected(): void
    {
        $this->actingAsAdmin();
        BotConfig::set('ai_max_tokens', '500');

        $response = $this->post(route('cms.konfigurasi.update'), ['ai_max_tokens' => 999999]);

        $response->assertSessionHasErrors('ai_max_tokens');
        $this->assertSame('500', BotConfig::get('ai_max_tokens'));
    }

    public function test_ai_temperature_above_bound_is_rejected(): void
    {
        $this->actingAsAdmin();
        BotConfig::set('ai_temperature', '0.7');

        $response = $this->post(route('cms.konfigurasi.update'), ['ai_temperature' => 5]);

        $response->assertSessionHasErrors('ai_temperature');
        $this->assertSame('0.7', BotConfig::get('ai_temperature'));
    }

    public function test_unknown_provider_in_order_is_rejected(): void
    {
        $this->actingAsAdmin();
        BotConfig::set('ai_provider_order', 'groq,gemini');

        $response = $this->post(route('cms.konfigurasi.update'), ['ai_provider_order' => 'groq,grok']);

        $response->assertSessionHasErrors('ai_provider_order');
        $this->assertSame('groq,gemini', BotConfig::get('ai_provider_order'));
    }

    public function test_valid_values_are_saved(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('cms.konfigurasi.update'), [
            'ai_max_tokens'     => 800,
            'ai_temperature'    => 0.9,
            'ai_provider_order' => 'groq,gemini,openrouter',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('800', BotConfig::get('ai_max_tokens'));
        $this->assertSame('0.9', BotConfig::get('ai_temperature'));
        $this->assertSame('groq,gemini,openrouter', BotConfig::get('ai_provider_order'));
    }

    public function test_ai_rag_top_k_above_bound_is_rejected(): void
    {
        $this->actingAsAdmin();
        BotConfig::set('ai_rag_top_k', '5');

        $response = $this->post(route('cms.konfigurasi.update'), ['ai_rag_top_k' => 999]);

        $response->assertSessionHasErrors('ai_rag_top_k');
        $this->assertSame('5', BotConfig::get('ai_rag_top_k'));
    }

    public function test_ai_rag_min_score_above_bound_is_rejected(): void
    {
        $this->actingAsAdmin();
        BotConfig::set('ai_rag_min_score', '0.55');

        $response = $this->post(route('cms.konfigurasi.update'), ['ai_rag_min_score' => 5.0]);

        $response->assertSessionHasErrors('ai_rag_min_score');
        $this->assertSame('0.55', BotConfig::get('ai_rag_min_score'));
    }
}
