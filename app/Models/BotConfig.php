<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BotConfig extends Model
{
    protected $fillable = ['key', 'value', 'type', 'label', 'description', 'group'];

    // Memo per-request (bukan per-worker) — CACHE_STORE=database bikin tiap Cache::remember()
    // jadi round-trip MySQL, dan satu ProcessAiReply::handle() sendirian memanggil get() ~12x.
    // Direset otomatis tiap request/job baru karena worker PHP-nya stateless per proses.
    private static array $requestMemo = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$requestMemo)) {
            return static::$requestMemo[$key] ?? $default;
        }

        // Cache menyimpan raw value DB (atau null kalau key tidak ada) — BUKAN $default.
        // $default sengaja tidak ikut ter-cache: kalau ter-capture di closure, pemanggil
        // pertama untuk key yang belum ada di DB akan "mengunci" defaultnya selama 300 detik
        // untuk semua pemanggil lain yang mengirim default berbeda.
        $cached = Cache::remember('bot_config_' . $key, 300, function () use ($key) {
            $config = static::where('key', $key)->first();
            return ['v' => $config?->value];
        });

        $raw = $cached['v'] ?? null;
        static::$requestMemo[$key] = $raw;

        return $raw ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('bot_config_' . $key);
        unset(static::$requestMemo[$key]);
    }

    // Encapsulates the 'bot_config_' cache-key prefix so callers that need to invalidate a
    // key written outside set() (e.g. a background job writing straight to the DB) don't have
    // to hardcode the prefix themselves and risk drifting from it.
    public static function forget(string $key): void
    {
        Cache::forget('bot_config_' . $key);
        unset(static::$requestMemo[$key]);
    }

    // Called between queue jobs (see AppServiceProvider::boot()) — a long-lived queue:work
    // process must not keep serving one job's memo to the next job.
    public static function clearRequestMemo(): void
    {
        static::$requestMemo = [];
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::get($key);
        if ($value === null) return $default;
        return in_array(strtolower((string) $value), ['true', '1', 'yes']);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) (static::get($key) ?? $default);
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        return (float) (static::get($key) ?? $default);
    }
}
