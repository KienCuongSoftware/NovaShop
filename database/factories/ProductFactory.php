<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'brand_id' => null,
            'name' => fake()->unique()->sentence(4),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 999),
            'old_price' => null,
            'image' => null,
            'quantity' => fake()->numberBetween(1, 200),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
