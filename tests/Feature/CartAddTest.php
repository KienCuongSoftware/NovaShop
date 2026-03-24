<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAddTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_simple_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'quantity' => 10,
        ]);

        $this->actingAs($user)
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }
}
