<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\RecommendationEventLogger;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationEventLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_logs_impression_events_for_suggested_section(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::query()->create(['name' => 'TrackBrand']);
        Product::factory()->count(6)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession(['recent_category_ids' => [$category->id]])
            ->get('/?rec_ab=v2');

        $response->assertOk();
        $this->assertDatabaseHas('recommendation_events', [
            'event_type' => RecommendationEventLogger::EVENT_IMPRESSION,
            'variant' => RecommendationService::VARIANT_V2,
            'source' => 'suggested',
        ]);
    }

    public function test_product_detail_logs_click_from_suggested_source(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('products.show', [
                'product' => $product,
                'rec_src' => 'suggested',
                'rec_variant' => 'v2',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('recommendation_events', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'event_type' => RecommendationEventLogger::EVENT_CLICK,
            'variant' => RecommendationService::VARIANT_V2,
        ]);
    }

    public function test_cart_add_logs_add_to_cart_from_suggested_source(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'quantity' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'rec_src' => 'suggested',
                'rec_variant' => 'v2',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recommendation_events', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'event_type' => RecommendationEventLogger::EVENT_ADD_TO_CART,
            'variant' => RecommendationService::VARIANT_V2,
        ]);
    }
}
