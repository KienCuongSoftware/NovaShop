<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Một số bản cài: bảng orders được tạo trước khi migration coupon chạy alter → thiếu coupon_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('coupons')) {
            return;
        }
        if (Schema::hasColumn('orders', 'coupon_id') && Schema::hasColumn('orders', 'discount_amount')) {
            return;
        }
        if (! Schema::hasColumn('orders', 'coupon_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('coupon_id')->nullable()->after('user_id')->constrained('coupons')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('orders', 'discount_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('discount_amount')->default(0);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
