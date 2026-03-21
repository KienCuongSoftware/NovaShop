<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'shipping_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('shipping_status', 20)->default('pending')->after('payment_status');
            });
        }

        DB::statement("
            UPDATE orders
            SET shipping_status = CASE
                WHEN status IN ('unpaid', 'payment_failed', 'pending', 'processing') THEN 'pending'
                WHEN status = 'shipping' THEN 'shipping'
                WHEN status IN ('awaiting_delivery', 'completed') THEN 'delivered'
                WHEN status = 'cancelled' THEN 'cancelled'
                WHEN status = 'return_refund' THEN 'returned'
                ELSE 'pending'
            END
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'shipping_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('shipping_status');
            });
        }
    }
};

