<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProviderContract;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\GroqProvider;
use App\Services\Ai\Providers\NvidiaProvider;
use App\Services\Ai\Providers\OpenRouterProvider;

final class ProviderRegistry
{
    /** @var array<string, AiProviderContract> */
    private array $providers;

    public function __construct(
        GroqProvider $groq,
        GeminiProvider $gemini,
        OpenRouterProvider $openrouter,
        NvidiaProvider $nvidia,
    ) {
        $this->providers = [
            'groq'       => $groq,
            'gemini'     => $gemini,
            'openrouter' => $openrouter,
            'nvidia'     => $nvidia,
        ];
    }

    public function get(string $name): ?AiProviderContract
    {
        return $this->providers[$name] ?? null;
    }
}
