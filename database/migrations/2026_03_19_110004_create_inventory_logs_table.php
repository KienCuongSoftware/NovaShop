<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('type', ['import', 'export', 'adjust']);
            $table->integer('quantity');
            $table->string('source', 50)->nullable(); // checkout, cancel, admin_adjust...
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'created_at'], 'idx_inventory_logs_variant_created');
            $table->index(['product_id', 'created_at'], 'idx_inventory_logs_product_created');
            $table->index(['order_id', 'created_at'], 'idx_inventory_logs_order_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};

