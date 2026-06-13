<?php

namespace App\Services;

use App\Models\BotConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class NvidiaService extends OpenAiCompatibleService
{
    public function __construct()
    {
        $this->providerName = 'NVIDIA NIM';
        $this->apiKey       = BotConfig::get('nvidia_api_key') ?: (config('services.nvidia.key') ?? '');
        $this->model        = BotConfig::get('nvidia_model')   ?: (config('services.nvidia.model') ?? 'qwen/qwen3.5-397b-a17b');
        $this->endpoint     = 'https://integrate.api.nvidia.com/v1/chat/completions';
    }

    // Nemotron Ultra 550B is a reasoning model — needs longer HTTP timeout than default 20s
    protected function httpClient(): PendingRequest
    {
        return Http::timeout(120)->withToken($this->apiKey);
    }
}
