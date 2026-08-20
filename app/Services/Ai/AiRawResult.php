<?php

namespace App\Services\Ai;

/** Result of a single wire-level call — the provider-level equivalent of the legacy 4-key array. */
final readonly class AiRawResult
{
    public function __construct(
        public bool $success,
        public string $text,
        public ?int $tokens,
        public ?string $error,
        public ?int $cooldownSeconds = null,
    ) {}

    public static function ok(string $text, ?int $tokens): self
    {
        return new self(true, $text, $tokens, null);
    }

    /** $cooldownSeconds is a provider-supplied Retry-After hint — null means "no hint, let the caller decide". */
    public static function fail(string $error, ?int $cooldownSeconds = null): self
    {
        return new self(false, '', null, $error, $cooldownSeconds);
    }
}
