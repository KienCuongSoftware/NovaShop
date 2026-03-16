<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'size', 'color', 'quantity', 'price_adjustment'];

    protected $casts = [
        'quantity' => 'integer',
        'price_adjustment' => 'decimal:0',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->product->price + $this->price_adjustment);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([$this->size, $this->color], fn ($v) => $v !== null && $v !== '');
        return implode(' / ', $parts) ?: '—';
    }
}
