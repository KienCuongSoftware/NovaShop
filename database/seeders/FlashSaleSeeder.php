<?php

namespace Database\Seeders;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FlashSaleSeeder extends Seeder
{
    /** Các khung giờ trong ngày (giống Shopee): [start_hour, end_hour]. */
    protected array $slotTimes = [
        [0, 2],   // 00:00 → 02:00
        [2, 4],   // 02:00 → 04:00
        [10, 12], // 10:00 → 12:00
        [12, 14], // 12:00 → 14:00
    ];

    public function run(): void
    {
        FlashSaleItem::query()->delete();
        FlashSale::query()->delete();

        $variants = ProductVariant::with('product')->get();
        if ($variants->isEmpty()) {
            $this->command->warn('Không có biến thể sản phẩm. Bỏ qua FlashSaleSeeder.');
            return;
        }

        $today = Carbon::today();
        $created = 0;

        // Tạo slot cho hôm nay và ngày mai (để luôn có slot "tiếp theo" khi hết ngày)
        foreach ([0, 1] as $dayOffset) {
            $date = $today->copy()->addDays($dayOffset);
            foreach ($this->slotTimes as [$startHour, $endHour]) {
                $startTime = $date->copy()->setTime($startHour, 0, 0);
                $endTime = $date->copy()->setTime($endHour, 0, 0);
                $name = sprintf('Flash Sale %s - %02d:00→%02d:00', $date->format('d/m'), $startHour, $endHour);

                $flashSale = FlashSale::create([
                    'name' => $name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => FlashSale::STATUS_ACTIVE,
                ]);

                foreach ($variants as $variant) {
                    $originalPrice = (float) $variant->price;
                    $salePrice = (int) round($originalPrice * (80 + random_int(0, 15)) / 100);
                    if ($salePrice >= $originalPrice) {
                        $salePrice = max(1, (int) round($originalPrice * 0.85));
                    }
                    FlashSaleItem::create([
                        'flash_sale_id' => $flashSale->id,
                        'product_variant_id' => $variant->id,
                        'sale_price' => $salePrice,
                        'quantity' => random_int(10, 50),
                        'sold' => 0,
                    ]);
                }

                $created++;
                $this->command->info("  Slot: {$name} — " . $variants->count() . ' sản phẩm.');
            }
        }

        $this->command->info("Đã tạo {$created} khung Flash Sale, mỗi khung có toàn bộ " . $variants->count() . ' biến thể.');
    }
}
