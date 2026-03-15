<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            if ($status === Order::STATUS_PENDING_PAYMENT) {
                $query->pendingPaymentTab();
            } elseif (array_key_exists($status, Order::statusLabels())) {
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
        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'payment_status' => $order->payment_method === Order::PAYMENT_METHOD_PAYPAL ? Order::PAYMENT_STATUS_FAILED : $order->payment_status,
        ]);
        return redirect()->route('orders.index')->with('success', 'Đã hủy đơn hàng #' . $order->id);
    }
}
