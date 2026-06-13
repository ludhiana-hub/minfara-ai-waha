<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConversationAnalysis extends Model
{
    protected $fillable = [
        'phone_number', 'contact_name', 'session_date', 'messages_count',
        'topic', 'sentiment', 'issue_detected', 'issue_type',
        'is_faq_gap', 'faq_gap_question', 'purchase_intent_score',
        'customer_segment', 'channel_source', 'keywords', 'summary',
        'resolved', 'analyzed_at',
    ];

    protected $casts = [
        'session_date'         => 'date',
        'issue_detected'       => 'boolean',
        'is_faq_gap'           => 'boolean',
        'resolved'             => 'boolean',
        'keywords'             => 'array',
        'purchase_intent_score'=> 'integer',
        'analyzed_at'          => 'datetime',
    ];

    public const SEGMENTS = [
        'hot_lead'        => 'Hot Lead',
        'window_shopper'  => 'Window Shopper',
        'objector'        => 'Objector',
        'loyalist'        => 'Loyalist',
        'churner_signal'  => 'Churner Signal',
        'unknown'         => 'Unknown',
    ];

    public const SEGMENT_COLORS = [
        'hot_lead'       => 'success',
        'window_shopper' => 'info',
        'objector'       => 'warning',
        'loyalist'       => 'primary',
        'churner_signal' => 'danger',
        'unknown'        => 'secondary',
    ];

    public function scopeForDate(Builder $q, string $date): Builder
    {
        return $q->where('session_date', $date);
    }

    public function scopeForPeriod(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('session_date', [$from, $to]);
    }

    public function getSegmentLabelAttribute(): string
    {
        return self::SEGMENTS[$this->customer_segment] ?? ucfirst($this->customer_segment ?? '-');
    }

    public function getSegmentColorAttribute(): string
    {
        return self::SEGMENT_COLORS[$this->customer_segment] ?? 'secondary';
    }

    public function getIntentBadgeAttribute(): string
    {
        $score = $this->purchase_intent_score ?? 0;
        if ($score >= 8) return 'success';
        if ($score >= 5) return 'warning';
        return 'secondary';
    }
}
