<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponDemoSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'Giảm 10% đơn đầu',
                'discount_type' => Coupon::TYPE_PERCENT,
                'discount_value' => 10,
                'min_order_amount' => 100000,
                'category_id' => null,
                'starts_at' => null,
                'ends_at' => null,
                'max_uses' => null,
                'uses_count' => 0,
                'is_active' => true,
            ]
        );
    }
}
