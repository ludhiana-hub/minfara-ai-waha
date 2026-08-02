<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRequestTrace extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'correlation_id', 'profile', 'provider', 'model', 'attempts', 'tool_rounds',
        'json_mode', 'success', 'error', 'tokens', 'latency_ms', 'attempt_log', 'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'json_mode'   => 'boolean',
            'success'     => 'boolean',
            'attempt_log' => 'array',
            'metadata'    => 'array',
            'created_at'  => 'datetime',
        ];
    }
}
