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
        if (! $this->hasIndex('products', 'idx_products_category_created')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['category_id', 'created_at'], 'idx_products_category_created');
            });
        }

        if (! $this->hasIndex('orders', 'idx_orders_user_created')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
            });
        }

        if (! $this->hasIndex('orders', 'idx_orders_status_payment_created')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['status', 'payment_status', 'created_at'], 'idx_orders_status_payment_created');
            });
        }

        if (! $this->hasIndex('order_items', 'idx_order_items_variant_product')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index(['product_variant_id', 'product_id'], 'idx_order_items_variant_product');
            });
        }

        if (! $this->hasIndex('payments', 'idx_payments_order_status_created')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['order_id', 'status', 'created_at'], 'idx_payments_order_status_created');
            });
        }

        if (! $this->hasIndex('product_reviews', 'idx_product_reviews_user_product_created')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->index(['user_id', 'product_id', 'created_at'], 'idx_product_reviews_user_product_created');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('product_reviews', 'idx_product_reviews_user_product_created')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropIndex('idx_product_reviews_user_product_created');
            });
        }

        if ($this->hasIndex('payments', 'idx_payments_order_status_created')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_order_status_created');
            });
        }

        if ($this->hasIndex('order_items', 'idx_order_items_variant_product')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex('idx_order_items_variant_product');
            });
        }

        if ($this->hasIndex('orders', 'idx_orders_status_payment_created')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_status_payment_created');
            });
        }

        if ($this->hasIndex('orders', 'idx_orders_user_created')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_user_created');
            });
        }

        if ($this->hasIndex('products', 'idx_products_category_created')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('idx_products_category_created');
            });
        }
    }
};
