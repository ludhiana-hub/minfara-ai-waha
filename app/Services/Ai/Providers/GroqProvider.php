<?php

namespace App\Services\Ai\Providers;

use App\Models\BotConfig;

class GroqProvider extends OpenAiCompatibleProvider
{
    public function name(): string
    {
        return 'groq';
    }

    protected function endpoint(): string
    {
        return 'https://api.groq.com/openai/v1/chat/completions';
    }

    protected function apiKey(): string
    {
        return BotConfig::get('groq_api_key') ?: (config('services.groq.key') ?? '');
    }

    protected function extraPayload(string $model): array
    {
        // reasoning_effort only applies to Groq's reasoning-capable gpt-oss family — sending it
        // to a non-gpt-oss fallback (e.g. llama-3.3-70b-versatile) risks a spurious 400 that's
        // indistinguishable in logs from a genuine decommission error, burning an attempt.
        return str_starts_with($model, 'openai/gpt-oss') ? ['reasoning_effort' => 'none'] : [];
    }
}
