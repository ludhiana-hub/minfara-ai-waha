<?php

namespace App\Services\Ai;

/**
 * Immutable-ish request builder (clone-based withers, matching the `withModel()` pattern
 * already used by the legacy AI services). Mode is expressed via *profile* + per-request
 * flags, not separate methods — see config/ai_profiles.php for what a profile controls.
 */
final class AiRequest
{
    private string $systemPrompt = '';

    /** @var array<int, array{role:string, content:string}> */
    private array $history = [];

    private int $maxTokens = 500;
    private float $temperature = 0.7;
    private bool $expectJson = false;

    /** @var string[] */
    private array $excludedProviders = [];

    private ?int $timeoutOverride = null;

    private function __construct(
        private readonly string $profile,
        private readonly string $userMessage,
    ) {}

    public static function make(string $profile, string $user): self
    {
        return new self($profile, $user);
    }

    public function withSystem(string $systemPrompt): self
    {
        $clone = clone $this;
        $clone->systemPrompt = $systemPrompt;

        return $clone;
    }

    /** @param array<int, array{role:string, content:string}> $history */
    public function withHistory(array $history): self
    {
        $clone = clone $this;
        $clone->history = $history;

        return $clone;
    }

    public function withMaxTokens(int $maxTokens): self
    {
        $clone = clone $this;
        $clone->maxTokens = $maxTokens;

        return $clone;
    }

    public function withTemperature(float $temperature): self
    {
        $clone = clone $this;
        $clone->temperature = $temperature;

        return $clone;
    }

    public function withTimeout(int $seconds): self
    {
        $clone = clone $this;
        $clone->timeoutOverride = $seconds;

        return $clone;
    }

    public function expectingJson(): self
    {
        $clone = clone $this;
        $clone->expectJson = true;

        return $clone;
    }

    /** @param string[] $providers */
    public function excludingProviders(array $providers): self
    {
        $clone = clone $this;
        $clone->excludedProviders = $providers;

        return $clone;
    }

    public function profile(): string
    {
        return $this->profile;
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function systemPrompt(): string
    {
        return $this->systemPrompt;
    }

    /** @return array<int, array{role:string, content:string}> */
    public function history(): array
    {
        return $this->history;
    }

    public function maxTokens(): int
    {
        return $this->maxTokens;
    }

    public function temperature(): float
    {
        return $this->temperature;
    }

    public function timeoutOverride(): ?int
    {
        return $this->timeoutOverride;
    }

    public function isExpectingJson(): bool
    {
        return $this->expectJson;
    }

    /** @return string[] */
    public function excludedProviders(): array
    {
        return $this->excludedProviders;
    }
}
