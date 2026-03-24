<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                'SELECT 1 as x FROM sqlite_master WHERE type = ? AND tbl_name = ? AND name = ? LIMIT 1',
                ['index', $table, $indexName]
            );

            return (bool) $row;
        }

        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, $table, $indexName]
        );

        return (bool) $row;
    }

    public function up(): void
    {
        if (! $this->hasIndex('orders', 'idx_orders_user')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('user_id', 'idx_orders_user');
            });
        }
        if (! $this->hasIndex('orders', 'idx_orders_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('status', 'idx_orders_status');
            });
        }
        // product_variants đã có index product_id từ bảng gốc
        if (! $this->hasIndex('inventory_logs', 'idx_inventory_variant')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->index('product_variant_id', 'idx_inventory_variant');
            });
        }
        if (Schema::hasTable('carts') && ! $this->hasIndex('carts', 'idx_cart_user')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->index('user_id', 'idx_cart_user');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('orders', 'idx_orders_user')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_user');
            });
        }
        if ($this->hasIndex('orders', 'idx_orders_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_status');
            });
        }
        if ($this->hasIndex('inventory_logs', 'idx_inventory_variant')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->dropIndex('idx_inventory_variant');
            });
        }
        if (Schema::hasTable('carts') && $this->hasIndex('carts', 'idx_cart_user')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->dropIndex('idx_cart_user');
            });
        }
    }
};
