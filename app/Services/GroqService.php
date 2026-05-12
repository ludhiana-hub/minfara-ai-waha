<?php

namespace App\Services;

use App\Models\BotConfig;

class GroqService extends OpenAiCompatibleService
{
    public function __construct()
    {
        $this->providerName = 'Groq';
        $this->apiKey       = BotConfig::get('groq_api_key') ?: (config('services.groq.key') ?? '');
        $this->model        = BotConfig::get('groq_model')   ?: (config('services.groq.model') ?? 'llama-3.3-70b-versatile');
        $this->endpoint     = 'https://api.groq.com/openai/v1/chat/completions';
    }
}
