<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SampleUsersSeeder extends Seeder
{
    /**
     * Tạo khoảng 50 người dùng mẫu, tất cả không phải admin.
     */
    public function run(): void
    {
        User::factory(50)->create([
            'is_admin' => false,
        ]);
    }
}
