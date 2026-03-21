<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chuẩn ảnh: product_images — ảnh chung gán product_id (product_variant_id = null),
     * ảnh riêng variant gán product_variant_id (và product_id của variant's product). Tránh ambiguous.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('product_images')) {
            DB::statement("ALTER TABLE product_images COMMENT = 'product_id: ảnh chung SP; product_variant_id: ảnh riêng biến thể (không trùng)'");
        }
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('product_images')) {
            DB::statement('ALTER TABLE product_images COMMENT = ""');
        }
    }
};
