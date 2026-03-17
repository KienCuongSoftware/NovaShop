<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_product_size_color_unique');
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['size', 'color', 'price_adjustment']);
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('price', 15, 0)->default(0)->after('product_id');
            $table->unsignedInteger('stock')->default(0)->after('price');
            $table->string('sku', 100)->nullable()->after('stock');
        });
        if (Schema::hasColumn('product_variants', 'quantity')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['price', 'stock', 'sku']);
            $table->string('size', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('price_adjustment', 12, 0)->default(0);
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique(['product_id', 'size', 'color'], 'product_variants_product_size_color_unique');
        });
    }
};
