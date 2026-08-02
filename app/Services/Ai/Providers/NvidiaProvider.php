<?php

namespace App\Services\Ai\Providers;

use App\Models\BotConfig;

class NvidiaProvider extends OpenAiCompatibleProvider
{
    public function name(): string
    {
        return 'nvidia';
    }

    protected function endpoint(): string
    {
        return 'https://integrate.api.nvidia.com/v1/chat/completions';
    }

    protected function apiKey(): string
    {
        return BotConfig::get('nvidia_api_key') ?: (config('services.nvidia.key') ?? '');
    }
}
