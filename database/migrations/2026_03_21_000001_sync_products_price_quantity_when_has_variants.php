<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chuẩn hóa Product vs Variant: với sản phẩm có biến thể, products.price = min(variants.price),
     * products.quantity = sum(variants.stock) — chỉ dùng để hiển thị; logic giá/tồn thật lấy từ variant.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('product_variants')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE products
                SET price = (
                    SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = products.id
                ),
                quantity = (
                    SELECT COALESCE(SUM(pv.stock), 0) FROM product_variants pv WHERE pv.product_id = products.id
                )
                WHERE EXISTS (SELECT 1 FROM product_variants pv2 WHERE pv2.product_id = products.id)
            ');

            return;
        }

        DB::statement('
            UPDATE products p
            INNER JOIN (
                SELECT product_id,
                    MIN(price) AS min_price,
                    COALESCE(SUM(stock), 0) AS total_stock
                FROM product_variants
                GROUP BY product_id
            ) v ON v.product_id = p.id
            SET p.price = v.min_price, p.quantity = v.total_stock
        ');
    }

    public function down(): void
    {
        // Không revert — chỉ sync một lần.
    }
};
