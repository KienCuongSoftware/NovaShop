<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cart_items', 'product_variant_id')) {
            return;
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            $conn = Schema::getConnection();
            $db = $conn->getDatabaseName();
            $idx = $conn->selectOne("SELECT index_name FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'cart_items' AND index_name LIKE '%cart_id%product_id%' AND index_name NOT LIKE '%variant%' LIMIT 1", [$db]);
            if ($idx && ! empty($idx->index_name)) {
                DB::statement('ALTER TABLE cart_items DROP INDEX '.$idx->index_name);
            }
            $exists = $conn->selectOne("SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'cart_items' AND index_name = 'cart_items_cart_product_variant_unique' LIMIT 1", [$db]);
            if (! $exists) {
                Schema::table('cart_items', function (Blueprint $table) {
                    $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_items_cart_product_variant_unique');
                });
            }

            return;
        }

        $named = DB::selectOne("SELECT 1 as x FROM sqlite_master WHERE type = 'index' AND tbl_name = 'cart_items' AND name = 'cart_items_cart_product_variant_unique' LIMIT 1");
        if (! $named) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_items_cart_product_variant_unique');
            });
        }
    }

    public function down(): void {}
};
