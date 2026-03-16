<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('size', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('price_adjustment', 12, 0)->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->unique(['product_id', 'size', 'color'], 'product_variants_product_size_color_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
