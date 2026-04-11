<?php

namespace Database\Seeders;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use App\Services\CatalogCache;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FlashSaleSeeder extends Seeder
{
    /**
     * Khung giờ trong ngày: [start_hour, end_hour] (end giờ đúng, ví dụ [18,20] = 18:00–20:00).
     * Có slot tối để trang chủ luôn có flash khi test buổi chiều/tối.
     */
    protected array $slotTimes = [
        [0, 2],
        [2, 4],
        [4, 6],
        [6, 8],
        [8, 10],
        [10, 12],
        [12, 14],
        [14, 16],
        [16, 18],
        [18, 20],
        [20, 22],
        [22, 24],
    ];

    public function run(): void
    {
        FlashSaleItem::query()->delete();
        FlashSale::query()->delete();

        // Mọi biến thể của SP active — trang chi tiết cần từng màu/size đều có dòng flash (trước đây chỉ MIN(id)/SP nên chọn variant khác là mất giá flash).
        $variants = ProductVariant::query()
            ->whereNull('deleted_at')
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('id')
            ->get(['id', 'price']);

        if ($variants->isEmpty()) {
            $this->command->warn('Không có biến thể sản phẩm (active). Bỏ qua FlashSaleSeeder.');

            return;
        }

        $this->command->info('Flash sale: '.$variants->count().' biến thể (toàn bộ màu/size SP active).');

        // Quanh ngày hiện tại để seed không “cũ” so với lịch máy
        $startDate = Carbon::today()->subDays(2)->startOfDay();
        $endDate = Carbon::today()->addDays(21)->endOfDay();

        $created = 0;
        $timestamps = now();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            foreach ($this->slotTimes as [$startHour, $endHour]) {
                $startTime = $date->copy()->setTime($startHour, 0, 0);
                // 22→24: kết thúc 00:00 ngày hôm sau (phủ 22:00–24:00, không để đêm trống)
                $endTime = $endHour >= 24
                    ? $date->copy()->addDay()->startOfDay()
                    : $date->copy()->setTime($endHour, 0, 0);
                $name = $endHour >= 24
                    ? sprintf('Flash Sale %s - %02d:00→24:00', $date->format('d/m'), $startHour)
                    : sprintf('Flash Sale %s - %02d:00→%02d:00', $date->format('d/m'), $startHour, $endHour);

                $flashSale = FlashSale::create([
                    'name' => $name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => FlashSale::computeStatus($startTime, $endTime),
                ]);

                $items = [];
                $batchSize = 800;
                foreach ($variants as $variant) {
                    $originalPrice = (float) $variant->price;
                    $salePrice = (int) round($originalPrice * (70 + random_int(0, 20)) / 100);
                    if ($salePrice >= $originalPrice) {
                        $salePrice = max(1, (int) round($originalPrice * 0.82));
                    }

                    $items[] = [
                        'flash_sale_id' => $flashSale->id,
                        'product_variant_id' => (int) $variant->id,
                        'sale_price' => $salePrice,
                        'quantity' => random_int(20, 120),
                        'sold' => 0,
                        'created_at' => $timestamps,
                        'updated_at' => $timestamps,
                    ];

                    if (count($items) >= $batchSize) {
                        FlashSaleItem::insert($items);
                        $items = [];
                    }
                }
                if (! empty($items)) {
                    FlashSaleItem::insert($items);
                }

                $created++;
            }
        }

        $this->command->info("Done: {$created} khung Flash Sale ({$startDate->toDateString()} → {$endDate->toDateString()}), mỗi khung {$variants->count()} SP.");

        CatalogCache::forgetFlashWelcome();
    }
}
