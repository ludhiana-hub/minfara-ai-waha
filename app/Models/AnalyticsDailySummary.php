<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDailySummary extends Model
{
    protected $fillable = [
        'summary_date', 'total_sessions', 'unique_customers',
        'bot_effectiveness_rate', 'sentiment_positive', 'sentiment_neutral',
        'sentiment_negative', 'top_topics', 'faq_gaps', 'avg_intent_score',
        'customer_segments', 'channel_sources',
    ];

    protected $casts = [
        'summary_date'      => 'date',
        'top_topics'        => 'array',
        'faq_gaps'          => 'array',
        'customer_segments' => 'array',
        'channel_sources'   => 'array',
    ];
}
