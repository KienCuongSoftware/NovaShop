<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $status = $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $query = $user->orders()->with(['items.product'])->latest();

        if ($status !== 'all' && in_array($status, array_keys(Order::statusLabels()))) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('id', 'like', '%' . $q . '%')
                    ->orWhereHas('items.product', function ($pq) use ($q) {
                        $pq->where('name', 'like', '%' . $q . '%');
                    });
            });
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('user.orders.index', compact('orders', 'status'));
    }

    public function checkout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        $request->validate([
            'shipping_address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($user, $cart, $request) {
            $total = 0;
            $order = $user->orders()->create([
                'status' => Order::STATUS_PENDING_PAYMENT,
                'shipping_address' => $request->input('shipping_address'),
                'phone' => $request->input('phone'),
                'notes' => $request->input('notes'),
            ]);

            foreach ($cart->items as $item) {
                $price = $item->product->price;
                $subtotal = $price * $item->quantity;
                $total += $subtotal;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $price,
                ]);
            }

            $order->update(['total_amount' => $total]);
            $cart->items()->delete();
        });

        return redirect()->route('orders.index')->with('success', 'Đặt hàng thành công.');
    }
}
