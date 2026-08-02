<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiCall;
use App\Services\Ai\AiRawResult;
use App\Services\Ai\Capability;

interface AiProviderContract
{
    /** Short lowercase key used in ai_provider_order and circuit-breaker cache keys. */
    public function name(): string;

    public function hasKey(): bool;

    public function supports(Capability $capability, string $model): bool;

    public function send(AiCall $call): AiRawResult;
}
