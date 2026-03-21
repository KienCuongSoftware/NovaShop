<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InventoryLog;
use Illuminate\Database\Seeder;

class InventoryLogSeeder extends Seeder
{
    /**
     * Bổ sung dữ liệu inventory_logs từ order_items hiện có (đúng đơn hàng + sản phẩm).
     * - Đơn chưa hủy: mỗi order_item → 1 log type=export (xuất kho khi đặt).
     * - Đơn đã hủy: mỗi order_item → 1 log type=import (trả kho khi hủy).
     */
    public function run(): void
    {
        InventoryLog::query()->delete();

        $orderItems = OrderItem::with(['order', 'product', 'productVariant'])->get();
        if ($orderItems->isEmpty()) {
            $this->command->warn('Chưa có order_item nào. Chạy OrderSeeder trước.');
            return;
        }

        $created = 0;
        foreach ($orderItems as $item) {
            $order = $item->order;
            if (!$order) {
                continue;
            }

            $isCancelled = $order->status === Order::STATUS_CANCELLED;
            $type = $isCancelled ? 'import' : 'export';
            $source = $isCancelled ? 'cancel' : 'order_seed';

            InventoryLog::create([
                'product_variant_id' => $item->product_variant_id,
                'order_id' => $order->id,
                'type' => $type,
                'quantity' => $item->quantity,
                'source' => $source,
                'note' => $isCancelled ? 'Hủy đơn #' . $order->id : 'Đơn hàng #' . $order->id,
            ]);
            $created++;
        }

        $this->command->info('Đã tạo ' . $created . ' bản ghi inventory_logs từ order_items.');
    }
}
