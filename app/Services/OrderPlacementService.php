<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\FlashSaleItem;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Jobs\ReleaseExpiredStockReservationJob;
use Illuminate\Support\Facades\DB;

/**
 * Tạo đơn từ giỏ (dùng chung web checkout và API v1).
 */
class OrderPlacementService
{
    /**
     * @param  array{
     *     payment_method: string,
     *     address_id?: int|null,
     *     full_name?: string,
     *     phone?: string,
     *     shipping_address?: string,
     *     lat?: float|string|null,
     *     lng?: float|string|null,
     *     notes?: string|null
     * }  $orderPayload  shipping_address_snapshot fields đã chuẩn hóa giống CheckoutController
     * @param  array{
     *     shipping_address_snapshot: string,
     *     phone_snapshot: string,
     *     lat: float|string|null,
     *     lng: float|string|null,
     *     address_id?: int|null
     * }  $resolvedAddress
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function placeFromCart(User $user, Cart $cart, string $paymentMethod, array $resolvedAddress, ?string $notes = null): array
    {
        $paymentStatus = Order::PAYMENT_STATUS_UNPAID;
        $initialStatus = $paymentMethod === Order::PAYMENT_METHOD_PAYPAL
            ? Order::STATUS_UNPAID
            : Order::STATUS_PENDING;

        $ttlMinutes = (int) env('STOCK_RESERVATION_TTL_MINUTES', 60);
        $reservationExpiresAt = $paymentMethod === Order::PAYMENT_METHOD_PAYPAL
            ? now()->addMinutes($ttlMinutes)
            : null;

        $orderPayload = [
            'status' => $initialStatus,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'shipping_status' => Order::mapShippingStatusFromOrderStatus($initialStatus),
            'notes' => $notes,
            'shipping_address_snapshot' => $resolvedAddress['shipping_address_snapshot'],
            'phone_snapshot' => $resolvedAddress['phone_snapshot'],
            'lat' => $resolvedAddress['lat'] ?? null,
            'lng' => $resolvedAddress['lng'] ?? null,
            'stock_reserved_expires_at' => $reservationExpiresAt,
            'stock_reserved_released_at' => null,
        ];
        if (! empty($resolvedAddress['address_id'])) {
            $orderPayload['address_id'] = $resolvedAddress['address_id'];
        }

        $shipping = ShippingFeeService::calculate(
            isset($orderPayload['lat']) ? (float) $orderPayload['lat'] : null,
            isset($orderPayload['lng']) ? (float) $orderPayload['lng'] : null
        );
        $orderPayload['shipping_fee'] = $shipping['fee'];
        $orderPayload['shipping_distance_km'] = $shipping['distance_km'];

        $order = null;

        try {
            DB::transaction(function () use ($user, $cart, $orderPayload, &$order) {
                $lockedCart = Cart::query()
                    ->whereKey($cart->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedCart) {
                    throw new \RuntimeException('Không tìm thấy giỏ hàng hợp lệ.');
                }

                $lockedItems = CartItem::query()
                    ->where('cart_id', $lockedCart->id)
                    ->lockForUpdate()
                    ->get();

                if ($lockedItems->isEmpty()) {
                    throw new \RuntimeException('Giỏ hàng đã được xử lý hoặc đang trống.');
                }

                $lockedItems->load(['product.category', 'productVariant.product.category']);
                $lockedCart->setRelation('items', $lockedItems);

                $total = 0;
                $order = $user->orders()->create(array_merge($orderPayload, [
                    'coupon_id' => null,
                    'discount_amount' => 0,
                ]));

                foreach ($lockedCart->items as $item) {
                    $qty = $item->quantity;
                    $price = $item->productVariant
                        ? (float) $item->productVariant->price
                        : (float) $item->product->price;

                    $lockedVariant = null;
                    $lockedProduct = null;
                    if ($item->product_variant_id) {
                        $lockedVariant = ProductVariant::query()
                            ->where('id', $item->product_variant_id)
                            ->lockForUpdate()
                            ->first();
                        if (! $lockedVariant || $lockedVariant->stock < $qty) {
                            throw new \RuntimeException('Biến thể sản phẩm không đủ tồn kho khi đặt hàng.');
                        }
                    } else {
                        $lockedProduct = Product::query()
                            ->where('id', $item->product_id)
                            ->lockForUpdate()
                            ->first();
                        if (! $lockedProduct || (int) $lockedProduct->quantity < $qty) {
                            throw new \RuntimeException('Sản phẩm không đủ tồn kho khi đặt hàng.');
                        }
                    }

                    if ($item->product_variant_id) {
                        $flashItem = FlashSaleItem::where('product_variant_id', $item->product_variant_id)
                            ->whereHas('flashSale', fn ($q) => $q->active())
                            ->lockForUpdate()
                            ->first();
                        if ($flashItem && $flashItem->quantity - $flashItem->sold >= $qty) {
                            $price = (float) $flashItem->sale_price;
                            $flashItem->increment('sold', $qty);
                        }
                    }

                    $total += $price * $qty;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'price' => $price,
                        'quantity' => $qty,
                    ]);

                    if ($lockedVariant) {
                        $lockedVariant->decrement('stock', $qty);
                    } elseif ($lockedProduct) {
                        $lockedProduct->decrement('quantity', $qty);
                    }
                    InventoryLog::create([
                        'product_variant_id' => $item->product_variant_id,
                        'order_id' => $order->id,
                        'type' => 'export',
                        'quantity' => $qty,
                        'source' => 'checkout',
                        'note' => 'Đặt hàng thành công, trừ tồn kho.',
                    ]);
                }

                $coupon = null;
                if ($lockedCart->coupon_id) {
                    $coupon = Coupon::query()->whereKey($lockedCart->coupon_id)->lockForUpdate()->first();
                }
                $couponResult = app(CouponService::class)->validateAndComputeDiscount($user, $lockedCart, $coupon);
                if (! $couponResult['ok']) {
                    throw new \RuntimeException($couponResult['message'] ?? 'Mã giảm giá không hợp lệ.');
                }
                $discountAmount = (int) $couponResult['discount'];
                $grandTotal = (int) $total - $discountAmount + (int) $orderPayload['shipping_fee'];
                if ($grandTotal < 0) {
                    $grandTotal = 0;
                }

                $order->update([
                    'total_amount' => $grandTotal,
                    'discount_amount' => $discountAmount,
                    'coupon_id' => $coupon?->id,
                    'shipping_fee' => $orderPayload['shipping_fee'],
                    'shipping_distance_km' => $orderPayload['shipping_distance_km'],
                ]);
                if ($coupon && $discountAmount > 0) {
                    $coupon->increment('uses_count');
                }
                $lockedCart->items()->delete();
                $lockedCart->update(['coupon_id' => null]);
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if (! $order) {
            return ['ok' => false, 'error' => 'Không thể tạo đơn hàng. Vui lòng thử lại.'];
        }

        try {
            $defaultRecVariant = (string) session('rec_ab_variant', RecommendationService::VARIANT_V1);
            $recCartVariants = (array) session('rec_cart_variants', []);
        } catch (\Throwable) {
            $defaultRecVariant = RecommendationService::VARIANT_V1;
            $recCartVariants = [];
        }
        app(RecommendationEventLogger::class)->logPurchaseForOrder($order, $defaultRecVariant, $recCartVariants);
        if (! empty($recCartVariants)) {
            $purchasedProductIds = $order->items()->pluck('product_id')->map(fn ($v) => (int) $v)->all();
            $remaining = array_diff_key($recCartVariants, array_flip($purchasedProductIds));
            try {
                session(['rec_cart_variants' => $remaining]);
            } catch (\Throwable) {
                // Ignore when API route has no session middleware.
            }
        }

        // Với PayPal: giữ tồn kho trong TTL, sau TTL nếu chưa thanh toán thành công thì hoàn tồn lại.
        if ($paymentMethod === Order::PAYMENT_METHOD_PAYPAL && $reservationExpiresAt) {
            ReleaseExpiredStockReservationJob::dispatch($order->id)
                ->delay($reservationExpiresAt);
        }

        return ['ok' => true, 'order' => $order->fresh()];
    }
}
