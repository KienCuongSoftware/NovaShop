<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đổi tên cột orders: shipping_address → shipping_address_snapshot, phone → phone_snapshot
     * để thể hiện rõ đây là bản chụp lúc đặt hàng; address_id là tham chiếu chuẩn khi có.
     */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'shipping_address') && !Schema::hasColumn('orders', 'shipping_address_snapshot')) {
            DB::statement('ALTER TABLE orders CHANGE shipping_address shipping_address_snapshot VARCHAR(500) NULL');
        }
        if (Schema::hasColumn('orders', 'phone') && !Schema::hasColumn('orders', 'phone_snapshot')) {
            DB::statement('ALTER TABLE orders CHANGE phone phone_snapshot VARCHAR(20) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'shipping_address_snapshot')) {
            DB::statement('ALTER TABLE orders CHANGE shipping_address_snapshot shipping_address VARCHAR(500) NULL');
        }
        if (Schema::hasColumn('orders', 'phone_snapshot')) {
            DB::statement('ALTER TABLE orders CHANGE phone_snapshot phone VARCHAR(20) NULL');
        }
    }
};
