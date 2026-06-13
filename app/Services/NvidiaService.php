<?php

namespace App\Services;

use App\Models\BotConfig;

class NvidiaService extends OpenAiCompatibleService
{
    public function __construct()
    {
        $this->providerName = 'NVIDIA NIM';
        $this->apiKey       = BotConfig::get('nvidia_api_key') ?: (config('services.nvidia.key') ?? '');
        $this->model        = BotConfig::get('nvidia_model')   ?: (config('services.nvidia.model') ?? 'nvidia/nemotron-3-ultra-550b-a55b');
        $this->endpoint     = 'https://integrate.api.nvidia.com/v1/chat/completions';
    }
}
