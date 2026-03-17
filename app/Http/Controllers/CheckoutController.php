<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product', 'items.productVariant'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $total = $cart->items->sum(fn ($i) => $i->subtotal);
        return view('user.checkout.show', compact('cart', 'total', 'user'));
    }

    public function placeOrder(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product', 'items.productVariant'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'payment_method' => 'required|in:cod,paypal',
            'notes' => 'nullable|string|max:1000',
        ], [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'shipping_address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
        ]);

        $paymentMethod = $request->input('payment_method');
        $paymentStatus = Order::PAYMENT_STATUS_UNPAID;
        $initialStatus = $paymentMethod === Order::PAYMENT_METHOD_PAYPAL
            ? Order::STATUS_PENDING_PAYMENT
            : Order::STATUS_PENDING;

        $order = null;
        DB::transaction(function () use ($user, $cart, $request, $paymentMethod, $paymentStatus, $initialStatus, &$order) {
            $total = 0;
            $order = $user->orders()->create([
                'status' => $initialStatus,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'shipping_address' => $request->input('shipping_address'),
                'phone' => $request->input('phone'),
                'notes' => $request->input('notes'),
            ]);

            foreach ($cart->items as $item) {
                $price = $item->productVariant
                    ? (float) $item->productVariant->price
                    : (float) $item->product->price;
                $qty = $item->quantity;
                $total += $price * $qty;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'price' => $price,
                    'quantity' => $qty,
                ]);
            }

            $order->update(['total_amount' => $total]);
            $cart->items()->delete();
        });

        if ($paymentMethod === Order::PAYMENT_METHOD_COD) {
            return redirect()->route('order.success', ['order' => $order->id])
                ->with('success', 'Đặt hàng thành công. Bạn sẽ thanh toán khi nhận hàng.');
        }

        return redirect()->route('paypal.create-order', ['order' => $order->id]);
    }
}
