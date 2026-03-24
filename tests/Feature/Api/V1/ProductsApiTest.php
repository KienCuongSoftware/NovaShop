<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_returns_paginated_json(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/products?per_page=2');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'price', 'effective_price', 'url'],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_products_show_returns_product_by_slug(): void
    {
        $product = Product::factory()->create(['name' => 'Demo API Product']);

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response->assertOk()
            ->assertJsonPath('data.slug', $product->slug)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_inactive_product_not_accessible_via_api(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->getJson('/api/v1/products/'.$product->slug)->assertNotFound();
    }
}
