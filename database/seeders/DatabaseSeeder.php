<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Tránh lỗi duplicate email khi chạy `db:seed` nhiều lần
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Nguyễn Văn Thử',
                'email' => 'test@example.com',
            ]);
        }

        $this->call([
            OrderSeeder::class,
            AddressSeeder::class,
            InventoryLogSeeder::class,
        ]);

        // Yêu thích / so sánh / thông báo / coupon mẫu (cần đã có sản phẩm trong DB)
        $this->call([NovaShopFeaturesSampleSeeder::class]);

        // Flash sale nhiều sản phẩm (1 biến thể đại diện / SP), khung giờ gồm cả tối
        $this->call([FlashSaleSeeder::class]);
    }
}
