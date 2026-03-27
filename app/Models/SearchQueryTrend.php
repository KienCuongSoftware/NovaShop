<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQueryTrend extends Model
{
    protected $fillable = [
        'keyword',
        'count',
        'last_seen_at',
    ];

    protected $casts = [
        'count' => 'integer',
    ];
}

