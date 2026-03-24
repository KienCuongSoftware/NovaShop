<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_tree_json(): void
    {
        $root = Category::factory()->create(['name' => 'Electronics']);
        Category::factory()->child($root)->create(['name' => 'Phones']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'image', 'parent_id', 'children'],
                ],
            ]);

        $rootPayload = collect($response->json('data'))->firstWhere('slug', $root->slug);
        $this->assertNotNull($rootPayload);
        $this->assertCount(1, $rootPayload['children']);
        $this->assertSame('Phones', $rootPayload['children'][0]['name']);
    }

    public function test_categories_supports_conditional_etag(): void
    {
        Category::factory()->create(['name' => 'Solo']);

        $first = $this->getJson('/api/v1/categories');
        $first->assertOk();
        $etag = $first->headers->get('ETag');
        $this->assertNotEmpty($etag);

        $second = $this->withHeaders(['If-None-Match' => $etag])->getJson('/api/v1/categories');
        $second->assertStatus(304);
    }
}
