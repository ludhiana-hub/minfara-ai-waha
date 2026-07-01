<?php

namespace App\Services;

use App\Models\BotConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class NvidiaService extends OpenAiCompatibleService
{
    private int $timeoutSeconds = 20;

    public function __construct()
    {
        $this->providerName = 'NVIDIA NIM';
        $this->apiKey       = BotConfig::get('nvidia_api_key') ?: (config('services.nvidia.key') ?? '');
        // NOTE: intentionally NOT reading BotConfig::get('nvidia_model') here — that key is dedicated to
        // ConversationAnalysisService's analytics primary (a heavy reasoning model, CMS-configurable).
        // This service's default must stay a fast model for the customer-chat pipeline; analytics always
        // overrides via withModel() anyway, so it's unaffected by this default.
        $this->model        = config('services.nvidia.model', 'meta/llama-3.1-8b-instruct');
        $this->endpoint     = 'https://integrate.api.nvidia.com/v1/chat/completions';
    }

    // Reasoning models (e.g. Nemotron Ultra) need a much longer HTTP timeout than the default 20s.
    public function withTimeout(int $seconds): static
    {
        $clone                 = clone $this;
        $clone->timeoutSeconds = $seconds;
        return $clone;
    }

    protected function httpClient(): PendingRequest
    {
        return Http::timeout($this->timeoutSeconds)->withToken($this->apiKey);
    }
}
