<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTarget extends Model
{
    protected $fillable = ['name', 'phone_number', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'target_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Normalize phone: strip non-digits, convert 0→62, +62→62 */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    /** Masked display: 628xxx****xxxx */
    public function getMaskedPhoneAttribute(): string
    {
        $p = $this->phone_number;
        if (strlen($p) <= 8) {
            return $p;
        }
        return substr($p, 0, 5) . '****' . substr($p, -4);
    }
}
