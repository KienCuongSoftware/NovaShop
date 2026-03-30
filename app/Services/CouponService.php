<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;

class CouponService
{
    /**
     * @return array{ok: bool, discount: int, message: ?string}
     */
    public function validateAndComputeDiscount(User $user, Cart $cart, ?Coupon $coupon): array
    {
        if (!$coupon) {
            return ['ok' => true, 'discount' => 0, 'message' => null];
        }

        if (!$coupon->isCurrentlyValid()) {
            return ['ok' => false, 'discount' => 0, 'message' => 'Mã giảm giá không còn hiệu lực.'];
        }

        // User segment rules
        $segment = (string) ($coupon->user_segment ?? Coupon::SEGMENT_ALL);
        if ($segment === Coupon::SEGMENT_VIP && ! (bool) ($user->is_vip ?? false)) {
            return ['ok' => false, 'discount' => 0, 'message' => 'Mã giảm giá chỉ áp dụng cho khách VIP.'];
        }

        if ((bool) ($coupon->first_order_only ?? false)) {
            $hasAnyOrder = Order::query()->where('user_id', $user->id)->exists();
            if ($hasAnyOrder) {
                return ['ok' => false, 'discount' => 0, 'message' => 'Mã này chỉ dành cho khách mua lần đầu (chưa có đơn hàng).'];
            }
        }

        if ((bool) ($coupon->birthday_only ?? false)) {
            if (! $user->birthday) {
                return ['ok' => false, 'discount' => 0, 'message' => 'Mã sinh nhật yêu cầu tài khoản có ngày sinh; vui lòng liên hệ hỗ trợ hoặc cập nhật hồ sơ.'];
            }
            $window = (int) ($coupon->birthday_window_days ?? 7);
            if (! $user->isWithinBirthdayCouponWindow($window)) {
                return ['ok' => false, 'discount' => 0, 'message' => 'Mã này chỉ dùng trong khoảng thời gian quanh ngày sinh nhật của bạn.'];
            }
        }

        if (! empty($coupon->min_completed_orders)) {
            $min = (int) $coupon->min_completed_orders;
            $completed = Order::query()
                ->where('user_id', $user->id)
                ->where('status', Order::STATUS_COMPLETED)
                ->count();
            if ($completed < $min) {
                return ['ok' => false, 'discount' => 0, 'message' => "Mã giảm giá yêu cầu tối thiểu {$min} đơn hoàn thành."];
            }
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
