<?php

namespace App\Services;

use App\Models\BotConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OpenRouterService extends OpenAiCompatibleService
{
    public function __construct()
    {
        $this->providerName = 'OpenRouter';
        $this->apiKey       = BotConfig::get('openrouter_api_key') ?: (config('services.openrouter.key') ?? '');
        $this->model        = BotConfig::get('openrouter_model')   ?: (config('services.openrouter.model') ?? 'deepseek/deepseek-chat-v3-0324:free');
        $this->endpoint     = 'https://openrouter.ai/api/v1/chat/completions';
    }

    protected function httpClient(): PendingRequest
    {
        return Http::timeout(20)
            ->withToken($this->apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url', 'https://mitfara.com'),
                'X-Title'      => config('app.name', 'MinFara AI Bot'),
            ]);
    }
}
