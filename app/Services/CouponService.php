<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;

class CouponService
{
    /**
     * @return array{ok: bool, discount: int, message: ?string}
     */
    public function validateAndComputeDiscount(Cart $cart, ?Coupon $coupon): array
    {
        if (!$coupon) {
            return ['ok' => true, 'discount' => 0, 'message' => null];
        }

        if (!$coupon->isCurrentlyValid()) {
            return ['ok' => false, 'discount' => 0, 'message' => 'Mã giảm giá không còn hiệu lực.'];
        }

        $cart->loadMissing(['items.product.category', 'items.productVariant']);
        $flashByVariant = CartPricingService::activeFlashItemsByVariantId();

        $categoryIds = null;
        if ($coupon->category_id) {
            $cat = Category::with('children')->find($coupon->category_id);
            $categoryIds = $cat ? collect($cat->getDescendantIds())->flip() : collect();
        }

        $eligibleSubtotal = 0.0;
        $fullSubtotal = 0.0;

        foreach ($cart->items as $item) {
            $line = CartPricingService::lineTotal($item, $flashByVariant);
            $fullSubtotal += $line;
            if ($categoryIds === null) {
                $eligibleSubtotal += $line;
            } else {
                $pid = (int) ($item->product->category_id ?? 0);
                if ($pid && $categoryIds->has($pid)) {
                    $eligibleSubtotal += $line;
                }
            }
        }

        if ($coupon->category_id && $eligibleSubtotal <= 0) {
            return ['ok' => false, 'discount' => 0, 'message' => 'Giỏ hàng không có sản phẩm thuộc danh mục áp dụng mã này.'];
        }

        $minBase = $coupon->category_id ? $eligibleSubtotal : $fullSubtotal;
        if ($minBase < (float) $coupon->min_order_amount) {
            return [
                'ok' => false,
                'discount' => 0,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để dùng mã (tối thiểu '.number_format($coupon->min_order_amount, 0, ',', '.').'₫).',
            ];
        }

        $discount = 0.0;
        if ($coupon->discount_type === Coupon::TYPE_PERCENT) {
            $pct = min(100, max(0, (int) $coupon->discount_value));
            $discount = round($eligibleSubtotal * $pct / 100);
        } else {
            $discount = min((float) $coupon->discount_value, $eligibleSubtotal);
        }

        $discount = (int) min($discount, $eligibleSubtotal);

        return ['ok' => true, 'discount' => $discount, 'message' => null];
    }
}
