<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('shipping_fee')->default(0)->after('total_amount')->comment('Phí ship (VNĐ)');
            $table->decimal('shipping_distance_km', 8, 2)->nullable()->after('shipping_fee')->comment('Khoảng cách từ kho đến địa chỉ giao (km)');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_fee', 'shipping_distance_km']);
        });
    }
};
