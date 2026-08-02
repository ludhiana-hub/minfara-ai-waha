<?php

namespace App\Services\Ai;

/**
 * Single wire-level call to one provider/model. Immutable — a retry on the same model
 * reuses the same AiCall instance.
 */
final readonly class AiCall
{
    /** @param array<int, array{role:string, content:string}> $messages */
    public function __construct(
        public string $model,
        public array $messages,
        public int $maxTokens,
        public float $temperature,
        public int $timeoutSeconds,
        public bool $jsonMode = false,
    ) {}

    public function withoutJsonMode(): self
    {
        return new self($this->model, $this->messages, $this->maxTokens, $this->temperature, $this->timeoutSeconds, false);
    }
}
