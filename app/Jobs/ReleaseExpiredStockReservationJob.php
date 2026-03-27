<?php

namespace App\Jobs;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredStockReservationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            /** @var Order|null $order */
            $order = Order::query()->whereKey($this->orderId)->lockForUpdate()->first();
            if (! $order) {
                return;
            }

            if ($order->payment_method !== Order::PAYMENT_METHOD_PAYPAL) {
                return;
            }

            if ($order->stock_reserved_expires_at === null || $order->stock_reserved_expires_at->gt(now())) {
                return;
            }

            // Idempotency: đã release rồi thì thôi.
            if (! empty($order->stock_reserved_released_at)) {
                return;
            }

            // Chỉ release nếu chưa thanh toán thành công.
            if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
                return;
            }

            // Chỉ release cho các trạng thái PayPal chưa hoàn tất.
            if (! in_array($order->status, [Order::STATUS_UNPAID, Order::STATUS_PAYMENT_FAILED], true)) {
                return;
            }

            $items = $order->items()->get(['product_id', 'product_variant_id', 'quantity']);

            foreach ($items as $item) {
                if (! empty($item->product_variant_id)) {
                    $variant = ProductVariant::query()
                        ->where('id', (int) $item->product_variant_id)
                        ->lockForUpdate()
                        ->first();
                    if ($variant) {
                        $variant->increment('stock', (int) $item->quantity);
                    }

                    InventoryLog::create([
                        'product_variant_id' => $item->product_variant_id,
                        'order_id' => $order->id,
                        'type' => 'import',
                        'quantity' => (int) $item->quantity,
                        'source' => 'reservation_expired',
                        'note' => 'Hết hạn giữ tồn kho do thanh toán PayPal chưa hoàn tất.',
                    ]);
                } else {
                    $product = Product::query()
                        ->where('id', (int) $item->product_id)
                        ->lockForUpdate()
                        ->first();
                    if ($product) {
                        $product->increment('quantity', (int) $item->quantity);
                    }

                    InventoryLog::create([
                        'product_variant_id' => null,
                        'order_id' => $order->id,
                        'type' => 'import',
                        'quantity' => (int) $item->quantity,
                        'source' => 'reservation_expired',
                        'note' => 'Hết hạn giữ tồn kho do thanh toán PayPal chưa hoàn tất.',
                    ]);
                }
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_CANCELLED),
                'payment_status' => Order::PAYMENT_STATUS_FAILED,
                'stock_reserved_released_at' => now(),
            ]);
        });
    }
}

