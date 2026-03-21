<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'product_id', 'product_variant_id', 'quantity', 'price'];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:0',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->price * $this->quantity);
    }
}
