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

    protected function extraPayload(): array
    {
        return ['reasoning_effort' => 'none'];
    }
}
