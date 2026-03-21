<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bỏ product_id khỏi inventory_logs — product_variant_id suy ra được product_id, tránh dư thừa và lệch dữ liệu.
     */
    public function up(): void
    {
        if (Schema::hasColumn('inventory_logs', 'product_id')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
            Schema::table('inventory_logs', function (Blueprint $table) {
                if (Schema::hasIndex('inventory_logs', 'idx_inventory_logs_product_created')) {
                    $table->dropIndex('idx_inventory_logs_product_created');
                }
                $table->dropColumn('product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('product_variant_id')->constrained('products')->nullOnDelete();
            $table->index(['product_id', 'created_at'], 'idx_inventory_logs_product_created');
        });
    }
};
