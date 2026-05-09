<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'from_number',
        'message_in',
        'message_out',
        'mode',
        'ai_tokens_used',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];
}
