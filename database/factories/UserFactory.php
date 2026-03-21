<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /** Tên Việt Nam mẫu: Họ + Tên (dùng cho fake name). */
    private static array $vietnameseNames = [
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

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = static::$vietnameseNames[array_rand(static::$vietnameseNames)];

        return [
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
