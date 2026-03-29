<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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

        if ($status !== 'all') {
            if ($status === Order::STATUS_UNPAID) {
                $query->pendingPaymentTab();
            } elseif (in_array($status, Order::tabStatusKeys(), true)) {
                $query->where('status', $status);
            }
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

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load(['items.product', 'items.productVariant']);

        return view('user.orders.show', compact('order'));
    }

    public function success(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }
        return view('user.order-success', compact('order'));
    }

    public function cancel(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }
        if (!$order->canCancel()) {
            return redirect()->route('orders.index')->with('error', 'Đơn hàng không thể hủy.');
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::query()->where('id', $item->product_variant_id)->lockForUpdate()->first();
                    if ($variant) {
                        $variant->increment('stock', $item->quantity);
                    }
                } else {
                    $product = Product::query()->where('id', $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $product->increment('quantity', $item->quantity);
                    }
                }

                InventoryLog::create([
                    'product_variant_id' => $item->product_variant_id,
                    'order_id' => $order->id,
                    'type' => 'import',
                    'quantity' => $item->quantity,
                    'source' => 'order_cancel',
                    'note' => 'Hủy đơn, hoàn lại tồn kho.',
                ]);
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_CANCELLED),
                'payment_status' => $order->payment_method === Order::PAYMENT_METHOD_PAYPAL ? Order::PAYMENT_STATUS_FAILED : $order->payment_status,
            ]);
        });
        return redirect()->route('orders.index')->with('success', 'Đã hủy đơn hàng #' . $order->id);
    }
}
