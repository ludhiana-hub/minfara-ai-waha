<?php

namespace App\Services\Ai\Providers;

use App\Models\BotConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OpenRouterProvider extends OpenAiCompatibleProvider
{
    public function name(): string
    {
        return 'openrouter';
    }

    protected function endpoint(): string
    {
        return 'https://openrouter.ai/api/v1/chat/completions';
    }

    protected function apiKey(): string
    {
        return BotConfig::get('openrouter_api_key') ?: (config('services.openrouter.key') ?? '');
    }

    protected function httpClient(int $timeoutSeconds): PendingRequest
    {
        return Http::timeout($timeoutSeconds)
            ->withToken($this->apiKey())
            ->withHeaders([
                'HTTP-Referer' => config('app.url', 'https://mitfara.com'),
                'X-Title'      => config('app.name', 'MinFara AI Bot'),
            ]);
    }
}
