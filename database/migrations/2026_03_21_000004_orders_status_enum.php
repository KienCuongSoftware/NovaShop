<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đổi orders.status sang ENUM để tránh sai chính tả, đồng nhất giá trị.
     */
    public function up(): void
    {
        if (! Schema::hasTable('orders') || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        $enum = "'unpaid','payment_failed','pending','processing','shipping','awaiting_delivery','completed','cancelled','return_refund','pending_payment'";
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM({$enum}) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");
    }
};
