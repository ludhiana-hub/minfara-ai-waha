<?php

namespace App\Console\Commands;

use App\Models\BotConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnsureWahaWebhook extends Command
{
    protected $signature   = 'waha:ensure-webhook';
    protected $description = 'Set WAHA webhook events (message, message.any, session.status)';

    public function handle(): int
    {
        $url     = BotConfig::get('waha_url')     ?: config('whatsapp.base_url', 'http://localhost:3000');
        $apiKey  = BotConfig::get('waha_api_key') ?: config('whatsapp.api_key', '');
        $session = BotConfig::get('waha_session') ?: config('whatsapp.session', 'default');
        $appUrl  = config('app.url');

        if (empty($appUrl) || str_contains($appUrl, 'localhost')) {
            $this->warn('APP_URL is localhost — skipping WAHA webhook setup (only needed in production)');
            return self::SUCCESS;
        }

        $webhookUrl = rtrim($appUrl, '/') . '/api/whatsapp/webhook';

        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Api-Key' => $apiKey])
                ->put("{$url}/api/sessions/{$session}", [
                    'config' => [
                        'webhooks' => [[
                            'url'    => $webhookUrl,
                            'events' => ['message', 'message.any', 'session.status'],
                        ]],
                    ],
                ]);

            if ($response->successful()) {
                $this->info("WAHA webhook set: {$webhookUrl} [message, message.any, session.status]");
                Log::info('waha:ensure-webhook — OK', ['url' => $webhookUrl]);

                // Simpan nomor WA bot sendiri untuk filter self-messages di webhook handler
                try {
                    $me = Http::timeout(5)
                        ->withHeaders(['X-Api-Key' => $apiKey])
                        ->get("{$url}/api/{$session}/me");
                    if ($me->ok() && $ownId = $me->json('id')) {
                        BotConfig::updateOrCreate(['key' => 'waha_own_number'], ['value' => $ownId]);
                        Log::info('waha:ensure-webhook — own number stored', ['id' => $ownId]);
                        $this->info("Own WA number stored: {$ownId}");
                    }
                } catch (\Exception $e) {
                    Log::warning('waha:ensure-webhook — could not fetch own number', ['error' => $e->getMessage()]);
                }
            } else {
                $this->warn("WAHA webhook update returned HTTP {$response->status()} — will retry on next deploy");
                Log::warning('waha:ensure-webhook — non-2xx', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Exception $e) {
            $this->warn("WAHA not reachable: {$e->getMessage()} — will retry on next deploy");
            Log::warning('waha:ensure-webhook — exception', ['error' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }
}
