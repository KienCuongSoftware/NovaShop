<?php

namespace Database\Seeders;

use App\Models\CompareItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockNotificationSubscription;
use App\Models\User;
use App\Models\WishlistItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu mẫu: coupon, yêu thích, so sánh, đơn « mua cùng », thông báo có hàng (in-app).
 *
 * Gán cho **mọi** user không phải admin (vd: Trần Văn Tâm, test@example.com…), không chỉ user id nhỏ nhất.
 *
 * Chạy: php artisan db:seed --class=NovaShopFeaturesSampleSeeder
 */
class NovaShopFeaturesSampleSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->where('is_admin', false)->orderBy('id')->get();
        if ($users->isEmpty()) {
            $this->command->warn('Không có user thường.');

            return;
        }

        $products = Product::query()->where('is_active', true)->with('variants')->orderBy('id')->get();
        if ($products->count() < 2) {
            $this->command->warn('Cần ít nhất 2 sản phẩm active.');

            return;
        }

        $this->seedCoupons();
        $this->seedBoughtTogetherOrders($users->first(), $products);

        $demoProduct = $products->first();
        $demoVariantId = $demoProduct->variants->first()?->id;

        foreach ($users as $user) {
            $this->seedWishlistAndCompare($user, $products);
            $this->seedDemoStockAlert($user, $demoProduct, $demoVariantId);
        }

        $this->command->info('NovaShop mẫu: '.$users->count().' user — wishlist, so sánh, thông báo có hàng (demo). Coupons + đơn mua cùng (nếu chưa có).');
    }

    protected function seedCoupons(): void
    {
        Coupon::query()->firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'Giảm 10% toàn shop',
                'discount_type' => Coupon::TYPE_PERCENT,
                'discount_value' => 10,
                'min_order_amount' => 100000,
                'category_id' => null,
                'starts_at' => null,
                'ends_at' => null,
                'max_uses' => null,
                'uses_count' => 0,
                'is_active' => true,
            ]
        );

        Coupon::query()->firstOrCreate(
            ['code' => 'GIAM50K'],
            [
                'name' => 'Giảm 50.000₫',
                'discount_type' => Coupon::TYPE_FIXED,
                'discount_value' => 50000,
                'min_order_amount' => 300000,
                'category_id' => null,
                'starts_at' => null,
                'ends_at' => null,
                'max_uses' => 100,
                'uses_count' => 0,
                'is_active' => true,
            ]
        );

        $firstLeafCategoryId = Product::query()->whereNotNull('category_id')->value('category_id');
        if ($firstLeafCategoryId) {
            Coupon::query()->firstOrCreate(
                ['code' => 'DM15'],
                [
                    'name' => 'Giảm 15% theo danh mục (mẫu)',
                    'discount_type' => Coupon::TYPE_PERCENT,
                    'discount_value' => 15,
                    'min_order_amount' => 200000,
                    'category_id' => $firstLeafCategoryId,
                    'starts_at' => null,
                    'ends_at' => null,
                    'max_uses' => null,
                    'uses_count' => 0,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedWishlistAndCompare(User $user, $products): void
    {
        WishlistItem::query()->where('user_id', $user->id)->delete();
        foreach ($products->take(4) as $p) {
            WishlistItem::query()->create([
                'user_id' => $user->id,
                'product_id' => $p->id,
            ]);
        }

        CompareItem::query()->where('user_id', $user->id)->delete();
        foreach ($products->take(4)->values() as $i => $p) {
            CompareItem::query()->create([
                'user_id' => $user->id,
                'product_id' => $p->id,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Thông báo in-app: đã có hàng (mẫu) — không cần SP hết hàng.
     */
    protected function seedDemoStockAlert(User $user, Product $product, ?int $variantId): void
    {
        StockNotificationSubscription::query()->where('user_id', $user->id)->delete();

        StockNotificationSubscription::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'email' => $user->email,
            'notified_at' => Carbon::now()->subHours(2),
            'seen_at' => null,
        ]);
    }

    protected function seedBoughtTogetherOrders(?User $firstUser, $products): void
    {
        if (!$firstUser) {
            return;
        }

        $already = Order::query()->where('notes', 'Đơn mẫu: thường mua cùng')->count();
        if ($already >= 3) {
            return;
        }

        $trio = $products->take(3);
        if ($trio->count() !== 3) {
            return;
        }

        for ($k = (int) $already; $k < 3; $k++) {
            $order = Order::query()->create([
                'user_id' => $firstUser->id,
                'coupon_id' => null,
                'discount_amount' => 0,
                'status' => Order::STATUS_COMPLETED,
                'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_COMPLETED),
                'payment_method' => Order::PAYMENT_METHOD_COD,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'shipping_address_snapshot' => '123 Đường Mẫu, Q.1, TP.HCM',
                'phone_snapshot' => '0901234567',
                'lat' => 10.7769,
                'lng' => 106.7009,
                'shipping_fee' => 25000,
                'shipping_distance_km' => 5,
                'notes' => 'Đơn mẫu: thường mua cùng',
                'total_amount' => 0,
            ]);

            $sum = 0;
            foreach ($trio as $p) {
                $variant = $p->variants->firstWhere('stock', '>', 0) ?? $p->variants->first();
                $price = $variant ? (float) $variant->price : (float) $p->price;
                $qty = 1;
                $sum += (int) ($price * $qty);
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $qty,
                    'price' => $price,
                ]);
            }
            $order->update(['total_amount' => $sum + (int) $order->shipping_fee]);
        }
    }
}
