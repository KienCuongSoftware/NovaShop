<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'payment_method', 'transaction_id', 'amount', 'status'];

    protected $casts = [
        'amount' => 'decimal:0',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
