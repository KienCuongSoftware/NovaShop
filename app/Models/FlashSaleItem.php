<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleItem extends Model
{
    protected $fillable = ['flash_sale_id', 'product_variant_id', 'sale_price', 'quantity', 'sold'];

    protected $casts = [
        'sale_price' => 'decimal:0',
        'quantity' => 'integer',
        'sold' => 'integer',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class, 'flash_sale_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Số lượng còn lại cho flash sale (không vượt quá quantity - sold). */
    public function getRemainingAttribute(): int
    {
        return max(0, $this->quantity - $this->sold);
    }

    /** Lấy flash sale item đang active cho một variant (dùng cho giỏ hàng / checkout). */
    public static function activeForVariant(?int $productVariantId): ?self
    {
        if (!$productVariantId) {
            return null;
        }
        return self::where('product_variant_id', $productVariantId)
            ->whereHas('flashSale', fn ($q) => $q->active())
            ->with('flashSale')
            ->first();
    }
}
