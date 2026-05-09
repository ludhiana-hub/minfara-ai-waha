<?php

namespace App\Services;

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
        $this->url     = config('services.waha.url', 'http://localhost:3000');
        $this->apiKey  = config('services.waha.api_key', '');
        $this->session = config('services.waha.session', 'default');
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        try {
            $response = Http::timeout(10)
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

    public function aiFooter(): string
    {
        return "\n─────────────────\n_MinFara AI_ 🤖 _· Deutsch Lernen mit Fara_\nKetik *0* menu utama | *99* hubungi admin";
    }
}
