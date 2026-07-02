<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeSuggestion extends Model
{
    protected $fillable = ['question', 'answer', 'example_phones', 'period_start', 'period_end', 'status'];

    protected $casts = [
        'example_phones' => 'array',
        'period_start'   => 'date',
        'period_end'     => 'date',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
