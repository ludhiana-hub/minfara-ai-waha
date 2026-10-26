<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class LeadProfile extends Model
{
    use Prunable;

    public const STAGE_NEW               = 'new';
    public const STAGE_EXPLORING         = 'exploring';
    public const STAGE_QUALIFIED         = 'qualified';
    public const STAGE_OBJECTION         = 'objection';
    public const STAGE_READY_TO_CHECKOUT = 'ready_to_checkout';
    public const STAGE_CHECKOUT_SENT     = 'checkout_sent';

    private const RETENTION_DAYS = 90;

    /** This turn's objection, if any — per-turn view for SalesPlaybook, never persisted. */
    public ?string $currentObjection = null;

    protected $fillable = [
        'from_number', 'contact_name', 'language', 'level', 'goal', 'package_interest',
        'objections', 'stage', 'checkout_pushed_at',
    ];

    protected $casts = [
        'objections'         => 'array',
        'checkout_pushed_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        return static::where('updated_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
