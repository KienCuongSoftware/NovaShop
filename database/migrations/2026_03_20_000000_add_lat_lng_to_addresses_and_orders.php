<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm lat/lng cho địa chỉ (map) và đơn hàng (tính phí ship theo khoảng cách).
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('address_line');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'lat')) {
                $table->decimal('lat', 10, 7)->nullable()->after('shipping_address_snapshot');
            }
            if (!Schema::hasColumn('orders', 'lng')) {
                $table->decimal('lng', 10, 7)->nullable()->after('lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
