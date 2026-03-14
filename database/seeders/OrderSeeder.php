<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Tạo đơn hàng mẫu cho tất cả người dùng (role user, không phải admin).
     * Mỗi user có nhiều đơn với các trạng thái khác nhau.
     */
    public function run(): void
    {
        $users = User::where('is_admin', false)->get();
        $products = Product::all();

        if ($products->isEmpty() || $users->isEmpty()) {
            return;
        }

        $statuses = [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_SHIPPING,
            Order::STATUS_AWAITING_DELIVERY,
            Order::STATUS_COMPLETED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
            Order::STATUS_RETURN_REFUND,
        ];

        foreach ($users as $user) {
            $orderCount = rand(3, 8);
            for ($i = 0; $i < $orderCount; $i++) {
                $status = $statuses[array_rand($statuses)];
                $itemCount = rand(1, min(4, $products->count()));
                $selectedProducts = $products->random($itemCount);

                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => $status,
                    'shipping_address' => 'Số ' . rand(1, 200) . ', Đường mẫu, Quận ' . rand(1, 12) . ', TP.HCM',
                    'phone' => '0' . rand(900000000, 999999999),
                    'total_amount' => 0,
                ]);

                $total = 0;
                foreach ($selectedProducts as $product) {
                    $qty = rand(1, min(3, (int) $product->quantity));
                    $price = $product->price;
                    $subtotal = $price * $qty;
                    $total += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'price' => $price,
                    ]);
                }

                $order->update(['total_amount' => $total]);
            }
        }
    }
}
