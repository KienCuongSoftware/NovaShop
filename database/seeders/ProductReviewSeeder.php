<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductReviewSeeder extends Seeder
{
    /**
     * Mỗi sản phẩm có khoảng bao nhiêu đánh giá.
     * Ví dụ: ~12 sản phẩm => khoảng 1k~1.5k rows (nhẹ hơn nhiều so với 1000).
     */
    protected int $perProductMin = 80;
    protected int $perProductMax = 120;

    public function run(): void
    {
        $products = Product::query()->select(['id'])->get();
        // Dùng đúng danh sách người dùng hiện có trong hệ thống.
        // (Tránh lọc sai do cột/flag thay đổi giữa các lần seed.)
        $users = User::query()->select(['id'])->get();

        if ($products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Không có product hoặc user để seed ProductReview.');
            return;
        }

        // Xóa cũ để đảm bảo mỗi sản phẩm có đúng "ít nhất" số lượng yêu cầu.
        ProductReview::query()->delete();

        $contentSamples = [
            'Shop giao hàng nhanh, đóng gói kỹ. Chất lượng đúng như mô tả.',
            'Hàng đẹp, form vừa vặn. Mặc lên rất thoải mái.',
            'Sản phẩm ổn áp, màu giống hình. Sẽ ủng hộ thêm lần sau.',
            'Thời gian giao nhanh. Sản phẩm dùng tốt, chất liệu ổn.',
            'Giá hợp lý, mua không hối hận. Đóng gói cẩn thận.',
            'Sản phẩm giống mô tả, chất lượng ổn. Đánh giá 5 sao!',
            'Mua về dùng thử thì rất ok. Nhân viên tư vấn nhiệt tình.',
            'Đường may gọn gàng, không bị lỗi. Rất hài lòng.',
            'Hàng dùng ổn, không có vấn đề gì. Giao nhanh đúng hẹn.',
            'Có chút nhỏ hơn mong đợi nhưng vẫn ổn so với giá.',
            'Sản phẩm hơi khác màu một chút nhưng vẫn dễ dùng.',
            'Chất liệu mềm, thấm hút tốt. Mình thích lắm.',
            'Thấy đáng tiền. Kết cấu chắc chắn, bền theo thời gian.',
        ];

        // Cân bằng để rating trung bình cao.
        // Trọng số: 5★ (60%), 4★ (25%), 3★ (10%), 2★ (4%), 1★ (1%)
        $pickRating = function (): int {
            $r = random_int(1, 100);
            if ($r <= 60) return 5;
            if ($r <= 85) return 4;
            if ($r <= 95) return 3;
            if ($r <= 99) return 2;
            return 1;
        };

        $pickTitle = function (int $rating): string {
            return match (true) {
                $rating >= 5 => 'Quá tốt, đáng mua',
                $rating === 4 => 'Rất hài lòng',
                $rating === 3 => 'Ổn, dùng được',
                $rating === 2 => 'Tạm ổn',
                default => 'Không như mong đợi',
            };
        };

        $batchSize = 500;

        foreach ($products as $product) {
            $this->command->info('Seeding reviews for product_id=' . $product->id);

            $variants = ProductVariant::query()
                ->where('product_id', $product->id)
                ->with('attributeValues.attribute')
                ->get();
            $variantLabels = $variants->map(function ($v) {
                $parts = $v->attributeValues->sortBy('attribute.name')->pluck('value')->all();
                return implode(', ', $parts) ?: null;
            })->filter()->values()->all();
            if (empty($variantLabels)) {
                $variantLabels = [null];
            }

            $rows = [];
            $createdAtBase = now();
            $perProduct = random_int($this->perProductMin, $this->perProductMax);

            for ($i = 0; $i < $perProduct; $i++) {
                $userId = $users[random_int(0, $users->count() - 1)]->id;
                $rating = $pickRating();

                // Trộn ngày tạo: 0..180 ngày trước
                $createdAt = $createdAtBase->copy()->subDays(random_int(0, 180))
                    ->subHours(random_int(0, 23))
                    ->subMinutes(random_int(0, 59));

                $isVerified = random_int(1, 100) <= 65; // 65% đã xác thực (mẫu)
                $variantClassification = $variantLabels[array_rand($variantLabels)];

                $rows[] = [
                    'product_id' => $product->id,
                    'user_id' => (int) $userId,
                    'rating' => $rating,
                    'title' => $pickTitle($rating),
                    'content' => $contentSamples[random_int(0, count($contentSamples) - 1)],
                    'variant_classification' => $variantClassification,
                    'is_verified' => $isVerified,
                    'is_approved' => $isVerified,
                    'approved_at' => $isVerified ? $createdAt : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                if (count($rows) >= $batchSize) {
                    ProductReview::query()->insert($rows);
                    $rows = [];
                }
            }

            if (!empty($rows)) {
                ProductReview::query()->insert($rows);
            }
        }

        $this->command->info('Done seeding ProductReview.');
    }
}

