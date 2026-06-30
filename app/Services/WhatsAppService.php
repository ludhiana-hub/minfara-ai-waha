<?php

namespace App\Services;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use Illuminate\Support\Facades\Cache;
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
        if (!str_contains($chatId, '@')) {
            $chatId .= '@c.us';
        }

        $sent = $this->doSend($chatId, $text);

        // @lid accounts: WAHA WEBJS sends `from` without suffix so we default to @c.us,
        // but @lid accounts can't receive via @c.us — retry with @lid suffix.
        if (!$sent && str_ends_with($chatId, '@c.us')) {
            $lidId = substr($chatId, 0, -4) . '@lid';
            $sent  = $this->doSend($lidId, $text);
        }

        return $sent;
    }

    public function startTyping(string $chatId): void
    {
        $this->doPresence($chatId, 'startTyping');
    }

    public function stopTyping(string $chatId): void
    {
        $this->doPresence($chatId, 'stopTyping');
    }

    private function doPresence(string $chatId, string $action): void
    {
        try {
            Http::timeout(3)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/{$action}", [
                    'session' => $this->session,
                    'chatId'  => $chatId,
                ]);
        } catch (\Exception) {}
    }

    private function doSend(string $chatId, string $text): bool
    {
        try {
            $response = Http::timeout(15)
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

            // Track message ID so handleOwnerReply can skip echo-back events (fromMe:true via API)
            $raw = $response->json('id');
            $key = is_array($raw) ? ($raw['_serialized'] ?? $raw['id'] ?? null) : $raw;
            if ($key) {
                Cache::put('bot_sent_' . md5((string) $key), true, now()->addMinutes(5));
            }

            return true;

        } catch (\Exception $e) {
            Log::error('WAHA sendMessage exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function isSessionWorking(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->get("{$this->url}/api/sessions/{$this->session}");

            return $response->ok() && ($response->json('status') === 'WORKING');
        } catch (\Exception) {
            return false;
        }
    }

    public function buildMainMenu(): string
    {
        return FaqMenu::active()->where('command', '0')->value('content')
            ?? "Hallo! Ketik *1* untuk Program Kursus atau *99* untuk hubungi admin.";
    }
}
