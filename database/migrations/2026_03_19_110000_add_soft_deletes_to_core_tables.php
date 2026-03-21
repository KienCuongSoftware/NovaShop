<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

