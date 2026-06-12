<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $fillable = ['name', 'trigger_key', 'message_body', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
