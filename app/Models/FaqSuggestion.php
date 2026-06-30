<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqSuggestion extends Model
{
    protected $fillable = ['question', 'frequency', 'example_phones', 'status', 'high_priority'];

    protected $casts = [
        'example_phones' => 'array',
        'high_priority'  => 'boolean',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
