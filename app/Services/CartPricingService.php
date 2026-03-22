<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\FlashSale;
use Illuminate\Support\Collection;

class CartPricingService
{
    /**
     * Đơn giá 1 dòng giỏ (đã tính flash sale nếu có).
     */
    public static function unitPriceForItem(CartItem $item, ?Collection $flashItemsByVariantId = null): float
    {
        $flashItemsByVariantId = $flashItemsByVariantId ?? self::activeFlashItemsByVariantId();
        if ($item->product_variant_id && $flashItemsByVariantId->has($item->product_variant_id)) {
            $fi = $flashItemsByVariantId->get($item->product_variant_id);
            if ($fi && (int) $fi->remaining > 0) {
                return (float) $fi->sale_price;
            }
        }

        return $item->productVariant
            ? (float) $item->productVariant->price
            : (float) $item->product->price;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{sale_price: mixed, remaining: int}>
     */
    public static function activeFlashItemsByVariantId(): Collection
    {
        $active = FlashSale::active()->with('items')->first();
        if (!$active) {
            return collect();
        }

        return $active->items->keyBy('product_variant_id');
    }

    public static function lineTotal(CartItem $item, ?Collection $flashItemsByVariantId = null): float
    {
        return self::unitPriceForItem($item, $flashItemsByVariantId) * (int) $item->quantity;
    }

    public static function cartSubtotal(\App\Models\Cart $cart, ?Collection $flashItemsByVariantId = null): float
    {
        $flashItemsByVariantId = $flashItemsByVariantId ?? self::activeFlashItemsByVariantId();
        $sum = 0.0;
        foreach ($cart->items as $item) {
            $sum += self::lineTotal($item, $flashItemsByVariantId);
        }

        return $sum;
    }
}
