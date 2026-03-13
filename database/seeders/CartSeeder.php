<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Thêm giỏ hàng mẫu cho tất cả người dùng bình thường (không phải admin).
     * Mỗi giỏ có nhiều sản phẩm.
     */
    public function run(): void
    {
        $users = User::where('is_admin', false)->get();
        $products = Product::all();

        if ($products->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            $cart = $user->cart()->firstOrCreate([]);

            $count = rand(2, min(8, $products->count()));
            $selectedProducts = $products->random($count);

            foreach ($selectedProducts as $product) {
                $maxQty = max(1, (int) $product->quantity);
                $quantity = rand(1, min(3, $maxQty));

                CartItem::updateOrCreate(
                    ['cart_id' => $cart->id, 'product_id' => $product->id],
                    ['quantity' => $quantity]
                );
            }
        }
    }
}
