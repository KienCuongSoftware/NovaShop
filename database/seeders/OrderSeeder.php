<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Tạo đơn hàng mẫu cho nhiều user (không admin), đúng với tài khoản và đánh giá:
     * - Mỗi user có nhiều đơn, trạng thái + thanh toán đa dạng.
     * - Sản phẩm trong đơn ưu tiên là sản phẩm user đó đã đánh giá (khớp với product_reviews).
     * - Order item dùng đúng product + variant (nếu có), giá/tồn kho từ variant/product.
     */
    public function run(): void
    {
        OrderItem::query()->delete();
        Order::query()->delete();

        $users = User::where('is_admin', false)->get();
        if ($users->isEmpty()) {
            $this->command->warn('Không có user (không phải admin) để tạo đơn.');
            return;
        }

        // User -> danh sách product_id đã đánh giá (để ưu tiên cho vào đơn)
        $reviewedByUser = ProductReview::query()
            ->select('user_id', 'product_id')
            ->distinct()
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('product_id')->unique()->values()->all())
            ->all();

        $products = Product::with('variants')->get();
        if ($products->isEmpty()) {
            $this->command->warn('Không có sản phẩm để tạo đơn.');
            return;
        }

        $productById = $products->keyBy('id');

        // Trạng thái đơn: đa dạng, unpaid chỉ cho PayPal
        $statusesPaypal = [
            Order::STATUS_UNPAID,
            Order::STATUS_PENDING,
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPING,
            Order::STATUS_AWAITING_DELIVERY,
            Order::STATUS_COMPLETED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];
        $statusesCod = [
            Order::STATUS_PENDING,
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPING,
            Order::STATUS_AWAITING_DELIVERY,
            Order::STATUS_COMPLETED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        $addresses = [
            'Số 12, Nguyễn Huệ, Q.1, TP.HCM',
            'Số 45, Lê Lợi, Q.3, TP.HCM',
            'Số 78, Hai Bà Trưng, Q.1, TP.HCM',
            'Số 23, Pasteur, Q.3, TP.HCM',
            'Số 90, Cách Mạng Tháng 8, Q.10, TP.HCM',
            'Số 56, Nguyễn Văn Linh, Q.7, TP.HCM',
        ];

        foreach ($users as $user) {
            $reviewedIds = $reviewedByUser[$user->id] ?? [];
            $orderCount = random_int(3, 8);

            for ($o = 0; $o < $orderCount; $o++) {
                $paymentMethod = random_int(0, 1) === 0 ? Order::PAYMENT_METHOD_COD : Order::PAYMENT_METHOD_PAYPAL;
                $statuses = $paymentMethod === Order::PAYMENT_METHOD_PAYPAL ? $statusesPaypal : $statusesCod;
                $status = $statuses[array_rand($statuses)];

                $paymentStatus = Order::PAYMENT_STATUS_UNPAID;
                if ($paymentMethod === Order::PAYMENT_METHOD_PAYPAL) {
                    if (in_array($status, [Order::STATUS_UNPAID, Order::STATUS_PAYMENT_FAILED], true)) {
                        $paymentStatus = $status === Order::STATUS_PAYMENT_FAILED ? Order::PAYMENT_STATUS_FAILED : Order::PAYMENT_STATUS_UNPAID;
                    } else {
                        $paymentStatus = Order::PAYMENT_STATUS_PAID;
                    }
                } else {
                    if ($status === Order::STATUS_COMPLETED) {
                        $paymentStatus = Order::PAYMENT_STATUS_PAID;
                    }
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => $status,
                    'shipping_status' => Order::mapShippingStatusFromOrderStatus($status),
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'shipping_address' => $addresses[array_rand($addresses)],
                    'phone' => '0' . random_int(900000000, 999999999),
                    'notes' => random_int(0, 1) ? 'Giao giờ hành chính.' : null,
                    'total_amount' => 0,
                ]);

                // Ngày đặt: rải trong 6 tháng gần đây
                $order->created_at = Carbon::now()->subDays(random_int(0, 180))->subHours(random_int(0, 23));
                $order->saveQuietly();

                $itemCount = random_int(1, min(4, $products->count()));
                $pickProductIds = $this->pickProductIdsForOrder($reviewedIds, $products->pluck('id')->all(), $itemCount);
                $total = 0;

                foreach ($pickProductIds as $productId) {
                    $product = $productById->get($productId);
                    if (!$product) {
                        continue;
                    }

                    $variant = null;
                    $price = 0;
                    $maxQty = 0;

                    if ($product->hasVariants() && $product->variants->isNotEmpty()) {
                        $withStock = $product->variants->filter(fn ($v) => $v->stock > 0);
                        if ($withStock->isEmpty()) {
                            continue;
                        }
                        $variant = $withStock->random();
                        $price = (float) $variant->price;
                        $maxQty = $variant->stock;
                    } else {
                        $q = (int) $product->quantity;
                        if ($q < 1) {
                            continue;
                        }
                        $price = (float) $product->price;
                        $maxQty = $q;
                    }

                    $qty = min(random_int(1, 3), $maxQty);
                    if ($qty < 1) {
                        continue;
                    }

                    $total += $price * $qty;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'quantity' => $qty,
                        'price' => $price,
                    ]);
                }

                if ($total > 0) {
                    $order->update(['total_amount' => $total]);
                } else {
                    $order->delete();
                }
            }
        }

        $this->command->info('Đã seed đơn hàng mẫu cho ' . $users->count() . ' user.');
    }

    /**
     * Chọn product_id cho một đơn: ưu tiên sản phẩm user đã đánh giá, còn thiếu thì random từ toàn bộ.
     */
    private function pickProductIdsForOrder(array $reviewedIds, array $allIds, int $count): array
    {
        $reviewedIds = array_values(array_intersect($reviewedIds, $allIds));
        $pool = $reviewedIds;
        $rest = array_values(array_diff($allIds, $reviewedIds));
        shuffle($rest);
        foreach ($rest as $id) {
            $pool[] = $id;
        }
        $pool = array_unique($pool);
        if (count($pool) <= $count) {
            return array_slice($pool, 0, $count);
        }
        $keys = array_rand($pool, $count);
        $keys = is_array($keys) ? $keys : [$keys];
        return array_map(fn ($k) => $pool[$k], $keys);
    }
}
