<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * order_items is created in 2026_03_14; product_variants in 2026_03_16_000000.
 * Adds FK after variants table exists (fixes fresh migrate on SQLite/tests).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items') || Schema::hasColumn('order_items', 'product_variant_id')) {
            return;
        }
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasColumn('order_items', 'product_variant_id')) {
            return;
        }
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};
