<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_v2_excludes_recently_viewed_products(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $category = Category::factory()->create();
        $brand = Brand::query()->create(['name' => 'Brand One']);

        $viewed = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
        $candidate = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $result = app(RecommendationService::class)->suggestV2(
            $user,
            [$viewed->id],
            [$category->id],
            8
        );

        $this->assertTrue($result->pluck('id')->contains($candidate->id));
        $this->assertFalse($result->pluck('id')->contains($viewed->id));
    }

    public function test_suggest_v2_uses_cart_signals_for_same_category(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $favCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        $brand = Brand::query()->create(['name' => 'Brand Two']);

        $inCart = Product::factory()->create([
            'category_id' => $favCategory->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
        $favCandidate = Product::factory()->create([
            'category_id' => $favCategory->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
        Product::factory()->count(3)->create([
            'category_id' => $otherCategory->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $cart = Cart::query()->create(['user_id' => $user->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $inCart->id,
            'quantity' => 2,
        ]);

        $result = app(RecommendationService::class)->suggestV2($user, [], [], 8);
        $topIds = $result->take(3)->pluck('id');

        $this->assertTrue($result->pluck('id')->contains($favCandidate->id));
        $this->assertTrue($topIds->contains($favCandidate->id));
    }
}
