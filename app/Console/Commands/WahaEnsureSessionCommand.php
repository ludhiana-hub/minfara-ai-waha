<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WahaEnsureSessionCommand extends Command
{
    protected $signature   = 'waha:ensure-session';
    protected $description = 'Restart WAHA session jika tidak WORKING';

    private const FAILURE_STREAK_CACHE_KEY  = 'waha_down_streak';
    private const CRITICAL_STREAK_THRESHOLD = 10; // ~10 menit beruntun di cadence everyMinute()

    public function __construct(private readonly WhatsAppService $whatsapp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $status = $this->whatsapp->sessionStatus();

        if ($status === 'WORKING') {
            if (Cache::get(self::FAILURE_STREAK_CACHE_KEY, 0) > 0) {
                Log::info('waha:ensure-session — session recovered, resetting failure streak');
            }
            Cache::forget(self::FAILURE_STREAK_CACHE_KEY);
            return self::SUCCESS;
        }

        // Read-then-write instead of Cache::increment() — the database cache driver (used in production,
        // CACHE_STORE=database) returns false on a missing key instead of initializing it to 0, unlike
        // array/redis. No concurrency concern here (single scheduler process, once a minute).
        $streak = Cache::get(self::FAILURE_STREAK_CACHE_KEY, 0) + 1;
        Cache::put(self::FAILURE_STREAK_CACHE_KEY, $streak, now()->addHours(1));

        // SCAN_QR_CODE butuh manusia scan QR di dashboard WAHA — restart tidak menyelesaikan apa-apa,
        // cuma bikin log berisik tiap menit.
        if ($status === 'SCAN_QR_CODE') {
            Log::warning('waha:ensure-session — session needs QR scan, skipping restart', [
                'status' => $status,
                'streak' => $streak,
            ]);
            $this->warn("Session status: {$status} — needs manual QR scan, not restarting.");
            return self::SUCCESS;
        }

        Log::warning('waha:ensure-session — session not WORKING, attempting start', [
            'status' => $status,
            'streak' => $streak,
        ]);
        $this->warn("Session status: {$status} — triggering start...");

        if ($this->whatsapp->startSession()) {
            Log::info('waha:ensure-session — session start triggered successfully');
            $this->info('Session start triggered.');
        } else {
            $this->error('Failed to start session.');
        }

        // Log::critical di sini murni mempermudah grep di storage/logs/laravel.log untuk downtime
        // berkepanjangan — TIDAK ada channel Slack/email terpasang, jadi ini bukan alert yang mem-page.
        if ($streak >= self::CRITICAL_STREAK_THRESHOLD) {
            Log::critical('waha:ensure-session — WAHA has been down for an extended period', [
                'status'           => $status,
                'consecutive_runs' => $streak,
            ]);
        }

        return self::SUCCESS;
    }
}
