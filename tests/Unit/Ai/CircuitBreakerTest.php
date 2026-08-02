<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Support\CircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_scope_produces_the_legacy_cache_key(): void
    {
        $breaker = new CircuitBreaker('');
        $breaker->markOpen('groq', 300);

        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('ai_provider_unhealthy_groq'));
    }

    public function test_scoped_breaker_does_not_affect_the_default_scope(): void
    {
        $chat = new CircuitBreaker('');
        $eval = new CircuitBreaker('eval');

        $eval->markOpen('groq', 300);

        $this->assertTrue($eval->isOpen('groq'));
        $this->assertFalse($chat->isOpen('groq'));
    }

    public function test_two_distinct_scopes_do_not_interfere(): void
    {
        $batch = new CircuitBreaker('batch');
        $eval  = new CircuitBreaker('eval');

        $batch->markOpen('nvidia', 300);

        $this->assertTrue($batch->isOpen('nvidia'));
        $this->assertFalse($eval->isOpen('nvidia'));
    }

    public function test_all_open_is_false_for_an_empty_provider_list(): void
    {
        $breaker = new CircuitBreaker('');

        $this->assertFalse($breaker->allOpen([]));
    }

    public function test_all_open_requires_every_provider_to_be_open(): void
    {
        $breaker = new CircuitBreaker('');
        $breaker->markOpen('groq', 300);

        $this->assertFalse($breaker->allOpen(['groq', 'gemini']));

        $breaker->markOpen('gemini', 300);

        $this->assertTrue($breaker->allOpen(['groq', 'gemini']));
    }

    public function test_clear_removes_the_mark(): void
    {
        $breaker = new CircuitBreaker('');
        $breaker->markOpen('groq', 300);
        $breaker->clear('groq');

        $this->assertFalse($breaker->isOpen('groq'));
    }
}
