<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'from_number',
        'contact_name',
        'ip_address',
        'message_in',
        'message_out',
        'mode',
        'ai_tokens_used',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /** Phone number formatted as +62xxx */
    public function getDisplayNumberAttribute(): string
    {
        $number = preg_replace('/@.*$/', '', $this->from_number);
        return '+' . ltrim($number, '+');
    }

    /** Contact name with number in parentheses, or just the number */
    public function getDisplayContactAttribute(): string
    {
        return $this->contact_name
            ? $this->contact_name . ' (' . $this->display_number . ')'
            : $this->display_number;
    }
}
