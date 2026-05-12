<?php

namespace App\Services\Contracts;

interface AiServiceInterface
{
    /**
     * @param  array<array{role: string, content: string}> $history
     * @return array{success: bool, reply: string, tokens: int|null, error: string|null}
     */
    public function chat(string $userMessage, string $systemPrompt, int $maxTokens, float $temperature, array $history): array;
}
