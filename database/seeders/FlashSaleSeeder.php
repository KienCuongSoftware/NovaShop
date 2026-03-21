<?php

namespace Database\Seeders;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FlashSaleSeeder extends Seeder
{
    /** Các khung giờ trong ngày: [start_hour, end_hour]. */
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

        $variants = ProductVariant::query()
            ->select(['id', 'price'])
            ->get();
        if ($variants->isEmpty()) {
            $this->command->warn('Không có biến thể sản phẩm. Bỏ qua FlashSaleSeeder.');
            return;
        }

        $startDate = Carbon::create(2026, 3, 19)->startOfDay();
        $endDate = Carbon::create(2026, 12, 31)->endOfDay();
        $now = now();

        $created = 0;
        $timestamps = now();

        // Tạo slot cho toàn bộ giai đoạn
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            foreach ($this->slotTimes as [$startHour, $endHour]) {
                $startTime = $date->copy()->setTime($startHour, 0, 0);
                $endTime = $date->copy()->setTime($endHour, 0, 0);
                $name = sprintf('Flash Sale %s - %02d:00→%02d:00', $date->format('d/m'), $startHour, $endHour);

                $status = FlashSale::STATUS_SCHEDULED;
                if ($startTime->lte($now) && $endTime->gt($now)) {
                    $status = FlashSale::STATUS_ACTIVE;
                } elseif ($endTime->lte($now)) {
                    $status = FlashSale::STATUS_ENDED;
                }

                $flashSale = FlashSale::create([
                    'name' => $name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => $status,
                ]);

                // Insert theo batch để chạy nhanh hơn
                $items = [];
                $batchSize = 1500;
                foreach ($variants as $variant) {
                    $originalPrice = (float) $variant->price;
                    $salePrice = (int) round($originalPrice * (80 + random_int(0, 15)) / 100);
                    if ($salePrice >= $originalPrice) {
                        $salePrice = max(1, (int) round($originalPrice * 0.85));
                    }

                    $items[] = [
                        'flash_sale_id' => $flashSale->id,
                        'product_variant_id' => (int) $variant->id,
                        'sale_price' => (int) $salePrice,
                        'quantity' => random_int(10, 50),
                        'sold' => 0,
                        'created_at' => $timestamps,
                        'updated_at' => $timestamps,
                    ];

                    if (count($items) >= $batchSize) {
                        FlashSaleItem::insert($items);
                        $items = [];
                    }
                }
                if (!empty($items)) {
                    FlashSaleItem::insert($items);
                }

                $created++;
                $this->command->info("  Slot: {$name} — " . $variants->count() . ' biến thể.');
            }
        }

        $this->command->info("Done: tạo {$created} khung Flash Sale cho " . $startDate->toDateString() . ' → ' . $endDate->toDateString() . ', mỗi khung có toàn bộ ' . $variants->count() . ' biến thể.');
    }
}
