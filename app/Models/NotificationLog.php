<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'template_id',
        'target_id',
        'phone_number',
        'rendered_message',
        'status',
        'waha_response',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(NotificationTarget::class, 'target_id');
    }
}
