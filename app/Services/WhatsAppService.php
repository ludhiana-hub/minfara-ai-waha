<?php

namespace App\Services;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $url;
    private string $apiKey;
    private string $session;

    public function __construct()
    {
        $this->url     = BotConfig::get('waha_url')     ?: config('services.waha.url', 'http://localhost:3000');
        $this->apiKey  = BotConfig::get('waha_api_key') ?: config('services.waha.api_key', '');
        $this->session = BotConfig::get('waha_session') ?: config('services.waha.session', 'default');
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        // WAHA WEBJS requires @c.us suffix for individual chats
        if (!str_contains($chatId, '@')) {
            $chatId .= '@c.us';
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/sendText", [
                    'session' => $this->session,
                    'chatId'  => $chatId,
                    'text'    => $text,
                ]);

            if ($response->failed()) {
                Log::error('WAHA sendText failed', [
                    'chatId' => $chatId,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('WAHA sendMessage exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function buildMainMenu(): string
    {
        return FaqMenu::active()->where('command', '0')->value('content')
            ?? "Hallo! Ketik *1* untuk Program Kursus atau *99* untuk hubungi admin.";
    }


}
