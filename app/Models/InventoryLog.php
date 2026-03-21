<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected $fillable = [
        'product_variant_id',
        'order_id',
        'type',
        'quantity',
        'source',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    /** Product suy ra từ productVariant; không lưu product_id để tránh dư thừa. */
    public function getProductAttribute(): ?Product
    {
        return $this->productVariant?->product;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }
}

