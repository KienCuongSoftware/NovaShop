<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\FlashSaleItem;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartPricingService;
use App\Services\CouponService;
use App\Services\ShippingFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product.category', 'items.productVariant', 'coupon'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $activeFlashSale = \App\Models\FlashSale::active()->with('items')->first();
        $addresses = $user->addresses()->orderByDesc('is_default')->orderBy('id')->get();
        $flashByVariant = $activeFlashSale?->items->keyBy('product_variant_id');
        $subtotal = (int) round(CartPricingService::cartSubtotal($cart, $flashByVariant));
        $coupon = $cart->coupon;
        $couponResult = app(CouponService::class)->validateAndComputeDiscount($cart, $coupon);
        if (!$couponResult['ok']) {
            if ($cart->coupon_id) {
                $cart->update(['coupon_id' => null]);
            }
            $discount = 0;
        } else {
            $discount = $couponResult['discount'];
        }
        $subtotalAfterDiscount = max(0, $subtotal - $discount);

        return view('user.checkout.show', compact('cart', 'user', 'activeFlashSale', 'addresses', 'subtotal', 'discount', 'subtotalAfterDiscount'));
    }

    /**
     * API tính phí ship theo tọa độ (dùng khi chọn địa chỉ trên checkout).
     * GET ?lat=...&lng=... hoặc không có → phí mặc định.
     */
    public function shippingFee(Request $request)
    {
        $lat = $request->query('lat') !== null && $request->query('lat') !== '' ? (float) $request->query('lat') : null;
        $lng = $request->query('lng') !== null && $request->query('lng') !== '' ? (float) $request->query('lng') : null;
        $result = ShippingFeeService::calculate($lat, $lng);
        return response()->json([
            'fee' => $result['fee'],
            'distance_km' => $result['distance_km'],
        ]);
    }

    public function placeOrder(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product.category', 'items.productVariant', 'coupon'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $request->validate(['payment_method' => 'required|in:cod,paypal'], ['payment_method.required' => 'Vui lòng chọn phương thức thanh toán.']);

        $useSavedAddress = $request->filled('address_id');
        $savedAddress = null;
        if ($useSavedAddress) {
            $savedAddress = $user->addresses()->find($request->input('address_id'));
            if (!$savedAddress) {
                return redirect()->route('checkout.show')->with('error', 'Địa chỉ không hợp lệ.');
            }
        } else {
            $request->validate([
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'shipping_address' => 'required|string|max:500',
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'payment_method' => 'required|in:cod,paypal',
                'notes' => 'nullable|string|max:1000',
            ], [
                'full_name.required' => 'Vui lòng nhập họ tên.',
                'phone.required' => 'Vui lòng nhập số điện thoại.',
                'shipping_address.required' => 'Vui lòng chọn địa chỉ trên bản đồ hoặc tìm kiếm.',
                'lat.required' => 'Vui lòng chọn vị trí giao hàng trên bản đồ.',
                'lng.required' => 'Vui lòng chọn vị trí giao hàng trên bản đồ.',
                'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            ]);
        }

        $paymentMethod = $request->input('payment_method');
        $paymentStatus = Order::PAYMENT_STATUS_UNPAID;
        $initialStatus = $paymentMethod === Order::PAYMENT_METHOD_PAYPAL
            ? Order::STATUS_UNPAID
            : Order::STATUS_PENDING;

        $orderPayload = [
            'status' => $initialStatus,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'shipping_status' => Order::mapShippingStatusFromOrderStatus($initialStatus),
            'notes' => $request->input('notes'),
        ];
        if ($savedAddress) {
            $orderPayload['address_id'] = $savedAddress->id;
            $orderPayload['shipping_address_snapshot'] = $savedAddress->full_address;
            $orderPayload['phone_snapshot'] = $savedAddress->phone;
            $orderPayload['lat'] = $savedAddress->lat;
            $orderPayload['lng'] = $savedAddress->lng;
        } else {
            $orderPayload['shipping_address_snapshot'] = $request->input('shipping_address');
            $orderPayload['phone_snapshot'] = $request->input('phone');
            $orderPayload['lat'] = $request->input('lat');
            $orderPayload['lng'] = $request->input('lng');
        }

        $shipping = ShippingFeeService::calculate(
            isset($orderPayload['lat']) ? (float) $orderPayload['lat'] : null,
            isset($orderPayload['lng']) ? (float) $orderPayload['lng'] : null
        );
        $orderPayload['shipping_fee'] = $shipping['fee'];
        $orderPayload['shipping_distance_km'] = $shipping['distance_km'];

        $order = null;
        try {
            DB::transaction(function () use ($user, $cart, $request, $orderPayload, &$order) {
            $total = 0;
            $order = $user->orders()->create(array_merge($orderPayload, [
                'coupon_id' => null,
                'discount_amount' => 0,
            ]));

            foreach ($cart->items as $item) {
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
                    if (!$lockedVariant || $lockedVariant->stock < $qty) {
                        throw new \RuntimeException('Biến thể sản phẩm không đủ tồn kho khi đặt hàng.');
                    }
                } else {
                    $lockedProduct = Product::query()
                        ->where('id', $item->product_id)
                        ->lockForUpdate()
                        ->first();
                    if (!$lockedProduct || (int) $lockedProduct->quantity < $qty) {
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

                // Trừ tồn kho và ghi log nhập/xuất kho.
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
            if ($cart->coupon_id) {
                $coupon = Coupon::query()->whereKey($cart->coupon_id)->lockForUpdate()->first();
            }
            $couponResult = app(CouponService::class)->validateAndComputeDiscount($cart, $coupon);
            if (!$couponResult['ok']) {
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
            $cart->items()->delete();
            $cart->update(['coupon_id' => null]);
            });
        } catch (\Throwable $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        if (!$order) {
            return redirect()->route('cart.index')->with('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
        }

        if ($paymentMethod === Order::PAYMENT_METHOD_COD) {
            return redirect()->route('order.success', ['order' => $order->id])
                ->with('success', 'Đặt hàng thành công. Bạn sẽ thanh toán khi nhận hàng.');
        }

        return redirect()->route('paypal.create-order', ['order' => $order->id]);
    }
}
