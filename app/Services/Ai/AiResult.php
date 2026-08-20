<?php

namespace App\Services\Ai;

use App\Services\Ai\Support\ErrorNormalizer;

final readonly class AiResult
{
    /**
     * @param array<string, array<string, string>> $attemptLog provider => model => outcome string
     * @param string[] $errors
     */
    public function __construct(
        public bool $success,
        public string $reply,
        public ?int $tokens,
        public ?string $provider,
        public ?string $model,
        public int $attempts,
        public array $attemptLog,
        public array $errors,
        public ?array $json = null,
        public ?string $correlationId = null,
    ) {}

    /** Drop-in for consumers still expecting the legacy AiServiceInterface::chat() shape. */
    public function toLegacyArray(): array
    {
        return [
            'success' => $this->success,
            'reply'   => $this->reply,
            'tokens'  => $this->tokens,
            'error'   => $this->success ? null : ($this->lastError() ?? 'unknown'),
        ];
    }

    public function lastError(): ?string
    {
        if ($this->success || empty($this->errors)) {
            return null;
        }

        // NOT end($this->errors) — end() takes its argument by reference to move the
        // array's internal pointer, which PHP treats as a mutation attempt on a readonly
        // property and throws. array_key_last() is purely functional, no reference needed.
        return $this->errors[array_key_last($this->errors)];
    }

    /** True when every failure this run was a quota/rate-limit error — used for a distinct
     * "everyone's overloaded" user-facing message rather than the generic fallback. */
    public function allErrorsAreQuotaExceeded(): bool
    {
        if (empty($this->errors)) {
            return false;
        }

        return count(array_filter(
            $this->errors,
            fn (string $e) => !in_array($e, [ErrorNormalizer::RATE_LIMITED, ErrorNormalizer::QUOTA_EXCEEDED], true)
        )) === 0;
    }
}
