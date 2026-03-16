<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'product_variant_id', 'quantity'];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getSubtotalAttribute(): float
    {
        $price = $this->productVariant
            ? (float) ($this->product->price + $this->productVariant->price_adjustment)
            : (float) $this->product->price;
        return $price * $this->quantity;
    }

    public function getVariantDisplayAttribute(): ?string
    {
        if (!$this->productVariant) {
            return null;
        }
        return $this->productVariant->display_name;
    }
}
