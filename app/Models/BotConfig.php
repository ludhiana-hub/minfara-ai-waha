<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BotConfig extends Model
{
    protected $fillable = ['key', 'value', 'type', 'label', 'description', 'group'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('bot_config_' . $key, 300, function () use ($key, $default) {
            $config = static::where('key', $key)->first();
            return $config ? $config->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('bot_config_' . $key);
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
