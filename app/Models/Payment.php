<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'payment_method',
        'gateway',
        'transaction_id',
        'amount',
        'status',
        'failure_reason',
        'response_payload',
        'error_code',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'response_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }
}
