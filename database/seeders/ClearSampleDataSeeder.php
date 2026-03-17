<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Xóa toàn bộ dữ liệu mẫu trong database, giữ lại bảng users.
 */
class ClearSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        Payment::query()->delete();
        OrderItem::query()->delete();
        Order::query()->delete();
        CartItem::query()->delete();
        Cart::query()->delete();
        if (Schema::hasTable('product_images')) {
            DB::table('product_images')->delete();
        }
        if (Schema::hasTable('product_attributes')) {
            DB::table('product_attributes')->delete();
        }
        if (Schema::hasTable('variant_attribute_values')) {
            DB::table('variant_attribute_values')->delete();
        }
        ProductVariant::query()->delete();
        Product::query()->delete();
        Category::query()->update(['parent_id' => null]);
        Category::query()->delete();
        Brand::query()->delete();
    }
}
