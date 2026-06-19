<?php

namespace App\Console\Commands;

use App\Models\BotConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaEnsureSessionCommand extends Command
{
    protected $signature   = 'waha:ensure-session';
    protected $description = 'Restart WAHA session jika tidak WORKING';

    public function handle(): int
    {
        $url     = BotConfig::get('waha_url')     ?: config('whatsapp.base_url', 'http://localhost:3000');
        $key     = BotConfig::get('waha_api_key') ?: config('whatsapp.api_key', '');
        $session = BotConfig::get('waha_session') ?: config('whatsapp.session', 'default');

        try {
            $res    = Http::timeout(5)->withHeaders(['X-Api-Key' => $key])->get("{$url}/api/sessions/{$session}");
            $status = $res->ok() ? ($res->json('status') ?? 'UNKNOWN') : 'UNREACHABLE';

            if ($status === 'WORKING') {
                return self::SUCCESS;
            }

            Log::warning('waha:ensure-session — session not WORKING, attempting start', ['status' => $status]);
            $this->warn("Session status: {$status} — triggering start...");

            $start = Http::timeout(10)->withHeaders(['X-Api-Key' => $key])->post("{$url}/api/sessions/{$session}/start");

            if ($start->successful()) {
                Log::info('waha:ensure-session — session start triggered successfully');
                $this->info('Session start triggered.');
            } else {
                Log::error('waha:ensure-session — failed to start session', ['http_status' => $start->status()]);
                $this->error("Failed to start session (HTTP {$start->status()}).");
            }
        } catch (\Exception $e) {
            Log::error('waha:ensure-session — exception', ['error' => $e->getMessage()]);
            $this->error('Exception: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
