<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UpdateUserNamesToVietnameseSeeder extends Seeder
{
    /**
     * Danh sách tên Việt Nam (giống với `database/factories/UserFactory.php`).
     */
    private array $vietnameseNames = [
        'Nguyễn Văn An', 'Trần Thị Bình', 'Lê Hoàng Chi', 'Phạm Minh Dũng', 'Hoàng Thị Hà',
        'Phan Văn Hải', 'Vũ Thị Hằng', 'Đặng Quang Hiếu', 'Bùi Thị Hoa', 'Đỗ Văn Hùng',
        'Nguyễn Thị Lan', 'Trần Minh Linh', 'Lê Thị Long', 'Phạm Văn Mai', 'Hoàng Thị Minh',
        'Phan Đức Nam', 'Vũ Thị Ngọc', 'Đặng Văn Phong', 'Bùi Thị Phương', 'Đỗ Minh Quân',
        'Nguyễn Thị Quyên', 'Trần Văn Tâm', 'Lê Thị Thanh', 'Phạm Hoàng Thảo', 'Hoàng Thị Trang',
        'Phan Văn Tuấn', 'Vũ Thị Tùng', 'Đặng Minh Việt', 'Bùi Thị Yến', 'Đỗ Văn Đức',
        'Nguyễn Thị Hương', 'Trần Văn Khoa', 'Lê Thị Kim', 'Phạm Văn Lâm', 'Hoàng Thị My',
        'Phan Thị Nga', 'Vũ Văn Sơn', 'Đặng Thị Thu', 'Bùi Văn Thành', 'Đỗ Thị Uyên',
        'Nguyễn Văn Cường', 'Trần Thị Dung', 'Lê Văn Đạt', 'Phạm Thị Giang', 'Hoàng Văn Huy',
    ];

    public function run(): void
    {
        $names = $this->vietnameseNames;
        if (empty($names)) {
            $this->command?->warn('Danh sách tên Việt Nam rỗng. Seeder không chạy.');
            return;
        }

        $total = User::query()->count();
        if ($total === 0) {
            $this->command?->warn('Không có user nào trong bảng `users` để update.');
            return;
        }

        $this->command?->info("Update name cho {$total} users sang tên Việt Nam...");

        User::query()->select(['id', 'name'])->chunkById(200, function ($users) use ($names) {
            foreach ($users as $user) {
                $user->name = $names[array_rand($names)];
                $user->save();
            }
        });
    }
}

