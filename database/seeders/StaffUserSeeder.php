<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\UserInitialsAvatarService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    /**
     * Tài khoản nhân viên mẫu — đăng nhập tại /staff/login
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Nhân viên NovaShop',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'is_staff' => true,
                'email_verified_at' => now(),
                'avatar_palette_index' => UserInitialsAvatarService::randomPaletteIndex(),
            ]
        );
    }
}
