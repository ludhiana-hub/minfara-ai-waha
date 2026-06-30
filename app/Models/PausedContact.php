<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PausedContact extends Model
{
    protected $fillable = ['from_number', 'contact_name', 'paused_until'];

    protected $casts = ['paused_until' => 'datetime'];

    public static function isPaused(string $from): bool
    {
        return static::where('from_number', $from)
            ->where('paused_until', '>', now())
            ->exists();
    }

    public static function pauseContact(string $from, ?string $contactName, int $minutes = 30): void
    {
        static::updateOrCreate(
            ['from_number' => $from],
            ['contact_name' => $contactName, 'paused_until' => now()->addMinutes($minutes)]
        );
    }
}
