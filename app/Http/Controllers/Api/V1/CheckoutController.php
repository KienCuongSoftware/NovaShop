<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPlacementService;
use App\Services\ShippingFeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Tính phí ship (cùng logic trang checkout).
     */
    public function shippingFee(Request $request): JsonResponse
    {
        $lat = $request->query('lat') !== null && $request->query('lat') !== '' ? (float) $request->query('lat') : null;
        $lng = $request->query('lng') !== null && $request->query('lng') !== '' ? (float) $request->query('lng') : null;
        $result = ShippingFeeService::calculate($lat, $lng);

        return response()->json([
            'fee' => $result['fee'],
            'distance_km' => $result['distance_km'],
        ]);
    }

    /**
     * Đặt hàng từ giỏ (COD hoặc PayPal — PayPal cần mở URL web).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $cart = $user->cart()->with(['items.product.category', 'items.productVariant', 'coupon'])->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng trống.'], 422);
        }

        $request->validate([
            'payment_method' => 'required|in:cod,paypal',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'full_name' => 'required_without:address_id|string|max:255',
            'phone' => 'required_without:address_id|string|max:20',
            'shipping_address' => 'required_without:address_id|string|max:500',
            'lat' => 'required_without:address_id|numeric|between:-90,90',
            'lng' => 'required_without:address_id|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
        ]);

        $useSavedAddress = $request->filled('address_id');
        if ($useSavedAddress) {
            $savedAddress = $user->addresses()->find($request->input('address_id'));
            if (! $savedAddress) {
                return response()->json(['message' => 'Địa chỉ không hợp lệ.'], 422);
            }
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

        $paymentMethod = $request->input('payment_method');
        $result = app(OrderPlacementService::class)->placeFromCart(
            $user,
            $cart,
            $paymentMethod,
            $resolvedAddress,
            $request->input('notes')
        );

        if (! $result['ok']) {
            return response()->json(['message' => $result['error']], 422);
        }

        $order = $result['order'];
        $orderPayload = [
            'id' => $order->id,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'total_amount' => (int) $order->total_amount,
            'shipping_fee' => (int) $order->shipping_fee,
            'discount_amount' => (int) $order->discount_amount,
        ];

        if ($paymentMethod === Order::PAYMENT_METHOD_COD) {
            return response()->json([
                'order' => $orderPayload,
                'next' => [
                    'action' => 'redirect',
                    'url' => route('order.success', ['order' => $order->id]),
                ],
            ], 201);
        }

        return response()->json([
            'order' => $orderPayload,
            'next' => [
                'action' => 'redirect',
                'url' => route('paypal.create-order', ['order' => $order]),
            ],
        ], 201);
    }
}
