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
        // Provider stopped because it hit max_tokens — the text is a usable prefix, not a finished reply.
        public bool $truncated = false,
    ) {}

    public static function ok(string $text, ?int $tokens, bool $truncated = false): self
    {
        return new self(true, $text, $tokens, null, null, $truncated);
    }

    /** $cooldownSeconds is a provider-supplied Retry-After hint — null means "no hint, let the caller decide". */
    public static function fail(string $error, ?int $cooldownSeconds = null): self
    {
        return new self(false, '', null, $error, $cooldownSeconds);
    }
}
