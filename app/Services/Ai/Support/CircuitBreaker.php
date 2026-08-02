<?php

namespace App\Services\Ai\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Per-scope provider health tracking. With scope='' this produces the EXACT cache key the
 * app has always used (`ai_provider_unhealthy_{provider}`) — the chat profile's breaker is
 * byte-identical to pre-AiRouter behavior. Other scopes (batch/eval) get their own
 * namespaced keys so a nightly analytics run or an eval sweep can never mark a provider
 * unhealthy for customer chat.
 */
final class CircuitBreaker
{
    public function __construct(private readonly string $scope = '') {}

    public function isOpen(string $provider): bool
    {
        return Cache::has($this->key($provider));
    }

    /** @param string[] $providers */
    public function allOpen(array $providers): bool
    {
        if (empty($providers)) {
            return false;
        }

        $open = array_filter($providers, fn (string $p) => $this->isOpen($p));

        return count($open) === count($providers);
    }

    public function markOpen(string $provider, int $ttlSeconds): void
    {
        Cache::put($this->key($provider), true, $ttlSeconds);
    }

    public function clear(string $provider): void
    {
        Cache::forget($this->key($provider));
    }

    private function key(string $provider): string
    {
        return 'ai_provider_unhealthy_' . ($this->scope !== '' ? $this->scope . '_' : '') . $provider;
    }
}
