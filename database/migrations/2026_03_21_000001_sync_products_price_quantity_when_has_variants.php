<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chuẩn hóa Product vs Variant: với sản phẩm có biến thể, products.price = min(variants.price),
     * products.quantity = sum(variants.stock) — chỉ dùng để hiển thị; logic giá/tồn thật lấy từ variant.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE products p
            INNER JOIN (
                SELECT product_id,
                    MIN(price) AS min_price,
                    COALESCE(SUM(stock), 0) AS total_stock
                FROM product_variants
                GROUP BY product_id
            ) v ON v.product_id = p.id
            SET p.price = v.min_price, p.quantity = v.total_stock
        ");
    }

    public function down(): void
    {
        // Không revert — chỉ sync một lần.
    }
};
