<?php

namespace Tests\Feature\Api\V1;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_login_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => 'password',
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user']);
    }

    public function test_auth_login_rejects_google_only_account_without_password(): void
    {
        User::factory()->create([
            'email' => 'googleonly@example.com',
            'password' => null,
            'google_id' => 'g-123',
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'googleonly@example.com',
            'password' => 'anything',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
        $this->assertStringContainsString('Google', $response->json('errors.email.0'));
    }

    public function test_cart_add_and_checkout_cod(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create(['quantity' => 10]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated()
            ->assertJsonPath('items.0.quantity', 2);

        $checkout = $this->postJson('/api/v1/checkout', [
            'payment_method' => 'cod',
            'full_name' => 'Nguyễn A',
            'phone' => '0900000000',
            'shipping_address' => '123 Đường ABC',
            'lat' => 10.762622,
            'lng' => 106.660172,
        ]);

        $checkout->assertCreated()
            ->assertJsonStructure(['order', 'next']);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);

        $user->refresh();
        $this->assertTrue($user->cart->items()->count() === 0);
    }

    public function test_checkout_with_saved_address(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $address = Address::create([
            'user_id' => $user->id,
            'full_name' => 'Trần B',
            'phone' => '0911111111',
            'province' => 'HCM',
            'district' => 'Q1',
            'ward' => 'P.Bến Nghé',
            'address_line' => '1 Lê Lợi',
            'lat' => 10.77,
            'lng' => 106.70,
            'is_default' => true,
        ]);
        $product = Product::factory()->create(['quantity' => 5]);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->postJson('/api/v1/checkout', [
            'payment_method' => 'cod',
            'address_id' => $address->id,
        ])->assertCreated();

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'address_id' => $address->id,
        ]);
    }

    public function test_checkout_api_rejects_second_attempt_with_same_cart(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create([
            'quantity' => 10,
            'price' => 100000,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $payload = [
            'payment_method' => 'cod',
            'full_name' => 'Nguyen Test',
            'phone' => '0900000000',
            'shipping_address' => '123 Test Street',
            'lat' => 10.762622,
            'lng' => 106.660172,
        ];

        $first = $this->postJson('/api/v1/checkout', $payload);
        $second = $this->postJson('/api/v1/checkout', $payload);

        $first->assertCreated()->assertJsonStructure(['order', 'next']);
        $second->assertStatus(422)->assertJsonPath('message', 'Giỏ hàng trống.');

        $this->assertSame(1, Order::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 8,
        ]);
    }

    public function test_checkout_api_returns_stock_error_when_inventory_drops_before_checkout(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create([
            'quantity' => 2,
            'price' => 100000,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        // Simulate another request consuming stock before this checkout starts.
        $product->update(['quantity' => 1]);

        $response = $this->postJson('/api/v1/checkout', [
            'payment_method' => 'cod',
            'full_name' => 'Stock Race',
            'phone' => '0900000000',
            'shipping_address' => '123 Test Street',
            'lat' => 10.762622,
            'lng' => 106.660172,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('không đủ tồn kho', mb_strtolower((string) $response->json('message')));
        $this->assertSame(0, Order::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 1,
        ]);
    }
}
