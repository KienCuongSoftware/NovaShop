<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->decimal('sale_price', 15, 0);
            $table->unsignedInteger('quantity')->default(0); // số lượng cho sale
            $table->unsignedInteger('sold')->default(0);     // đã bán
            $table->timestamps();

            $table->unique(['flash_sale_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_items');
    }
};
