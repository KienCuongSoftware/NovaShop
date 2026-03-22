<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $shippingStatus = $request->query('shipping_status', 'all');
        $q = trim((string) $request->query('q', ''));

        $query = Order::with(['user:id,name,email', 'items.product:id,name,image', 'items.productVariant'])
            ->latest();

        if ($status !== 'all') {
            if ($status === Order::STATUS_UNPAID) {
                $query->pendingPaymentTab();
            } elseif (in_array($status, Order::tabStatusKeys(), true)) {
                $query->where('status', $status);
            }
        }

        if ($shippingStatus !== 'all' && in_array($shippingStatus, Order::tabShippingStatusKeys(), true)) {
            $query->where('shipping_status', $shippingStatus);
        }

        if ($q !== '') {
            $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
            $query->where(function ($qb) use ($esc) {
                $qb->where('id', 'like', '%' . $esc . '%')
                    ->orWhere('phone_snapshot', 'like', '%' . $esc . '%')
                    ->orWhere('shipping_address_snapshot', 'like', '%' . $esc . '%')
                    ->orWhereHas('user', function ($uq) use ($esc) {
                        $uq->where('name', 'like', '%' . $esc . '%')
                            ->orWhere('email', 'like', '%' . $esc . '%');
                    });
            });
        }

        $orders = $query->paginate(7)->withQueryString();
        session(['admin.orders.page' => $orders->currentPage()]);

        return view('admin.orders.index', compact('orders', 'status', 'shippingStatus', 'q'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'coupon', 'items.product', 'items.productVariant.attributeValues.attribute']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $allowed = [
            Order::STATUS_UNPAID,
            Order::STATUS_PAYMENT_FAILED,
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPING,
            Order::STATUS_AWAITING_DELIVERY,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
            Order::STATUS_RETURN_REFUND,
        ];
        $request->validate([
            'status' => 'required|in:' . implode(',', $allowed),
        ], [
            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);

        $newStatus = (string) $request->input('status');
        $oldStatus = (string) $order->status;

        DB::transaction(function () use ($order, $newStatus, $oldStatus) {
            if ($newStatus === Order::STATUS_CANCELLED && $oldStatus !== Order::STATUS_CANCELLED) {
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
                        'source' => 'admin_cancel',
                        'note' => 'Admin cập nhật hủy đơn, hoàn tồn kho.',
                    ]);
                }
            }

            $order->update([
                'status' => $newStatus,
                'shipping_status' => Order::mapShippingStatusFromOrderStatus($newStatus),
            ]);

            if ($newStatus === Order::STATUS_COMPLETED && $order->payment_method === Order::PAYMENT_METHOD_COD) {
                $order->update(['payment_status' => Order::PAYMENT_STATUS_PAID]);
            }
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Đã cập nhật trạng thái đơn hàng #' . $order->id);
    }
}
