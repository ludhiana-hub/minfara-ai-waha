<?php

namespace App\Services;

use App\Models\BotConfig;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppSendThrottle
{
    private const LOCK_KEY = 'waha_send_throttle';
    private const LOCK_TTL_SECONDS = 120;

    // Waktu terjadwal saat pesan berikutnya boleh mulai dikirim.
    private const NEXT_SEND_AT_KEY = 'waha_next_send_at';
    private const SEND_COUNT_KEY = 'waha_send_count_5m';
    private const SEND_COUNT_TTL_SECONDS = 300; // 5 menit

    public function waitBeforeSend(): void
    {
        $enabled = BotConfig::getBool('waha_send_throttle_enabled', (bool) config('whatsapp.send_throttle.enabled', true));
        if (!$enabled) {
            return;
        }

        [$minDelay, $maxDelay] = $this->resolveDelayBounds();

        try {
            $waitSeconds = $this->reserveSendSlot($minDelay, $maxDelay);
        } catch (LockTimeoutException $e) {
            Log::warning('WhatsAppSendThrottle: lock timeout, applying conservative fallback wait', [
                'error' => $e->getMessage(),
            ]);
            $waitSeconds = $maxDelay;
        } catch (\Throwable $e) {
            Log::warning('WhatsAppSendThrottle: reserve failed, applying conservative fallback wait', [
                'error' => $e->getMessage(),
            ]);
            $waitSeconds = $maxDelay;
        }

        if ($waitSeconds > 0) {
            usleep($waitSeconds * 1_000_000);
        }
    }

    public function calculateDelaySeconds(int $volumeCount, int $minDelay, int $maxDelay): int
    {
        $base = ($maxDelay === 0 && $minDelay === 0)
            ? 0
            : random_int($minDelay, $maxDelay);

        return $base + $this->calculateVolumeBonus($volumeCount);
    }

    /**
     * Pilih tier volume tertinggi yang memenuhi count >= threshold.
     * Threshold 11 = mulai bonus saat sudah ada 11+ kirim dalam window 5 menit.
     */
    public function calculateVolumeBonus(int $volumeCount): int
    {
        $applicableTier = null;

        foreach (config('whatsapp.send_throttle.volume_tiers', []) as $tier) {
            $threshold = (int) ($tier['threshold'] ?? PHP_INT_MAX);
            if ($volumeCount >= $threshold) {
                $applicableTier = $tier;
            }
        }

        if ($applicableTier === null) {
            return 0;
        }

        $bonusMin = max(0, (int) ($applicableTier['bonus_min'] ?? 0));
        $bonusMax = max(0, (int) ($applicableTier['bonus_max'] ?? 0));
        if ($bonusMax < $bonusMin) {
            [$bonusMin, $bonusMax] = [$bonusMax, $bonusMin];
        }

        if ($bonusMax === 0 && $bonusMin === 0) {
            return 0;
        }

        return random_int($bonusMin, $bonusMax);
    }

    /**
     * Reserve slot kirim berikutnya di bawah lock, lalu tidur di luar lock.
     * Mencegah worker paralel saling senggol dan menghindari sleep panjang saat memegang lock.
     */
    private function reserveSendSlot(int $minDelay, int $maxDelay): int
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL_SECONDS);
        $lock->block(self::LOCK_TTL_SECONDS);

        try {
            $now = time();
            $nextAllowedAt = (int) (Cache::get(self::NEXT_SEND_AT_KEY, 0) ?: 0);
            $volumeCount = (int) (Cache::get(self::SEND_COUNT_KEY, 0) ?: 0);

            $delaySeconds = $this->calculateDelaySeconds($volumeCount, $minDelay, $maxDelay);
            $waitSeconds = max(0, $nextAllowedAt - $now);

            Cache::put(
                self::NEXT_SEND_AT_KEY,
                $now + $waitSeconds + $delaySeconds,
                now()->addHours(2),
            );
            $this->incrementSendCount();

            return $waitSeconds;
        } finally {
            try {
                $lock->release();
            } catch (\Throwable) {}
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveDelayBounds(): array
    {
        $minDelay = BotConfig::getInt('waha_send_delay_min', (int) config('whatsapp.send_throttle.min_delay', 5));
        $maxDelay = BotConfig::getInt('waha_send_delay_max', (int) config('whatsapp.send_throttle.max_delay', 10));

        $minDelay = max(0, $minDelay);
        $maxDelay = max(0, $maxDelay);
        if ($maxDelay < $minDelay) {
            [$minDelay, $maxDelay] = [$maxDelay, $minDelay];
        }

        return [$minDelay, $maxDelay];
    }

    private function incrementSendCount(): void
    {
        $current = (int) (Cache::get(self::SEND_COUNT_KEY, 0) ?: 0);
        Cache::put(self::SEND_COUNT_KEY, $current + 1, now()->addSeconds(self::SEND_COUNT_TTL_SECONDS));
    }
}
