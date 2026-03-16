<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('variants')->get();

        foreach ($products as $product) {
            if ($product->variants->isNotEmpty()) {
                continue;
            }
            $name = mb_strtolower($product->name ?? '');
            $sizes = null;
            $colors = null;

            if (preg_match('/\b(áo|quần|váy|váy|áo khoác|quần short)\b/u', $name)) {
                $sizes = ['S', 'M', 'L', 'XL'];
                $colors = ['Đen', 'Trắng', 'Xanh navy', 'Xám'];
            } elseif (preg_match('/\b(giày|dép|sandal)\b/u', $name)) {
                $sizes = ['36', '37', '38', '39', '40', '41', '42'];
                $colors = null;
            } elseif (preg_match('/\b(túi|ví|balo|cặp|túi xách)\b/u', $name)) {
                $sizes = null;
                $colors = ['Đen', 'Trắng', 'Nâu', 'Xanh', 'Đỏ'];
            } elseif (preg_match('/\b(nón|mũ)\b/u', $name)) {
                $sizes = null;
                $colors = ['Đen', 'Trắng', 'Xanh', 'Navy', 'Be'];
            } elseif (preg_match('/\b(bàn phím|chuột|tai nghe|loa|ốp lưng)\b/u', $name)) {
                $sizes = null;
                $colors = ['Đen', 'Trắng', 'Xám', 'Hồng'];
            } elseif (preg_match('/\b(đồng hồ|dây đeo)\b/u', $name)) {
                $sizes = null;
                $colors = ['Đen', 'Bạc', 'Vàng', 'Xanh'];
            } elseif (preg_match('/\b(áo thun|sơ mi|quần jean)\b/u', $name)) {
                $sizes = ['S', 'M', 'L'];
                $colors = ['Đen', 'Trắng', 'Xanh', 'Xám'];
            } else {
                $colors = ['Đen', 'Trắng'];
            }

            $this->createVariantsForProduct($product, $sizes, $colors);
        }
    }

    private function createVariantsForProduct(Product $product, ?array $sizes, ?array $colors): void
    {
        $sizeList = $sizes ?? [null];
        $colorList = $colors ?? [null];

        foreach ($sizeList as $size) {
            foreach ($colorList as $color) {
                if ($size === null && $color === null) {
                    continue;
                }
                $qty = random_int(5, 35);
                $adjustment = 0;
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $size,
                    'color' => $color,
                    'quantity' => $qty,
                    'price_adjustment' => $adjustment,
                ]);
            }
        }
    }
}
