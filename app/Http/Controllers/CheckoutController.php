<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartPricingService;
use App\Services\CouponService;
use App\Services\OrderPlacementService;
use App\Services\ShippingFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product.category', 'items.productVariant', 'coupon'])->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $activeFlashSale = \App\Models\FlashSale::active()->with('items')->first();
        $addresses = $user->addresses()->orderByDesc('is_default')->orderBy('id')->get();
        $flashByVariant = $activeFlashSale?->items->keyBy('product_variant_id');
        $subtotal = (int) round(CartPricingService::cartSubtotal($cart, $flashByVariant));
        $coupon = $cart->coupon;
        $couponResult = app(CouponService::class)->validateAndComputeDiscount($user, $cart, $coupon);
        if (! $couponResult['ok']) {
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

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $request->validate(['payment_method' => 'required|in:cod,paypal'], ['payment_method.required' => 'Vui lòng chọn phương thức thanh toán.']);

        $useSavedAddress = $request->filled('address_id');
        $savedAddress = null;
        if ($useSavedAddress) {
            $savedAddress = $user->addresses()->find($request->input('address_id'));
            if (! $savedAddress) {
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

        if ($savedAddress) {
            $resolvedAddress = [
                'address_id' => $savedAddress->id,
                'shipping_address_snapshot' => $savedAddress->full_address,
                'phone_snapshot' => $savedAddress->phone,
                'lat' => $savedAddress->lat,
                'lng' => $savedAddress->lng,
            ];
        } else {
            $resolvedAddress = [
                'shipping_address_snapshot' => (string) $request->input('shipping_address'),
                'phone_snapshot' => (string) $request->input('phone'),
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng'),
            ];
        }

        $result = app(OrderPlacementService::class)->placeFromCart(
            $user,
            $cart,
            $paymentMethod,
            $resolvedAddress,
            $request->input('notes')
        );

        if (! $result['ok']) {
            return redirect()->route('cart.index')->with('error', $result['error']);
        }

        $order = $result['order'];

        if ($paymentMethod === Order::PAYMENT_METHOD_COD) {
            return redirect()->route('order.success', ['order' => $order->id])
                ->with('success', 'Đặt hàng thành công. Bạn sẽ thanh toán khi nhận hàng.');
        }

        return redirect()->route('paypal.create-order', ['order' => $order->id]);
    }
}
