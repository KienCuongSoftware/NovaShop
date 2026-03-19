<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /** Địa chỉ mẫu theo tỉnh/thành — dùng cho user thật, không tạo dữ liệu linh tinh. */
    private array $provinces = [
        ['name' => 'TP. Hồ Chí Minh', 'districts' => ['Quận 1', 'Quận 3', 'Quận 7', 'Quận 10', 'Bình Thạnh', 'Phú Nhuận']],
        ['name' => 'Hà Nội', 'districts' => ['Quận Ba Đình', 'Quận Hoàn Kiếm', 'Quận Đống Đa', 'Quận Cầu Giấy', 'Quận Thanh Xuân']],
        ['name' => 'Đà Nẵng', 'districts' => ['Quận Hải Châu', 'Quận Thanh Khê', 'Quận Sơn Trà', 'Quận Ngũ Hành Sơn']],
        ['name' => 'Cần Thơ', 'districts' => ['Quận Ninh Kiều', 'Quận Bình Thủy', 'Quận Cái Răng']],
        ['name' => 'Bình Dương', 'districts' => ['Thủ Dầu Một', 'Thuận An', 'Dĩ An']],
    ];

    private array $wardStreetSamples = [
        'Số 12, đường Nguyễn Huệ',
        'Số 45, đường Lê Lợi',
        'Số 78, đường Hai Bà Trưng',
        'Số 23, đường Pasteur',
        'Số 90, đường Cách Mạng Tháng 8',
        'Số 56, đường Nguyễn Văn Linh',
        'Số 15, đường Lý Tự Trọng',
        'Số 88, đường Nam Kỳ Khởi Nghĩa',
        'Khu phố 3, đường 30/4',
        'Tổ 5, ấp Bình An',
    ];

    private array $labels = ['Nhà riêng', 'Công ty', 'Địa chỉ giao hàng'];

    public function run(): void
    {
        Address::query()->delete();

        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('Chưa có user nào. Chạy seed user trước.');
            return;
        }

        foreach ($users as $user) {
            $count = $user->is_admin ? 1 : random_int(1, 2);
            for ($i = 0; $i < $count; $i++) {
                $provinceRow = $this->provinces[array_rand($this->provinces)];
                $district = $provinceRow['districts'][array_rand($provinceRow['districts'])];
                $ward = 'Phường ' . (string) random_int(1, 15);
                $addressLine = $this->wardStreetSamples[array_rand($this->wardStreetSamples)] . ', ' . $ward . ', ' . $district . ', ' . $provinceRow['name'];

                Address::create([
                    'user_id' => $user->id,
                    'label' => $this->labels[$i % count($this->labels)],
                    'full_name' => $user->name,
                    'phone' => '0' . random_int(900000000, 999999999),
                    'province' => $provinceRow['name'],
                    'district' => $district,
                    'ward' => $ward,
                    'address_line' => $this->wardStreetSamples[array_rand($this->wardStreetSamples)],
                    'is_default' => $i === 0,
                ]);
            }
        }

        $this->command->info('Đã seed địa chỉ cho ' . $users->count() . ' user.');
    }
}
