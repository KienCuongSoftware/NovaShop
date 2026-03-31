<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderPlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPlacementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_from_cart_rejects_second_attempt_on_same_cart(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create([
            'quantity' => 10,
            'price' => 100000,
        ]);

        $cart = Cart::query()->create([
            'user_id' => $user->id,
            'coupon_id' => null,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 2,
        ]);

        $service = app(OrderPlacementService::class);
        $resolvedAddress = [
            'shipping_address_snapshot' => '123 Test Street',
            'phone_snapshot' => '0900000000',
            'lat' => 10.762622,
            'lng' => 106.660172,
        ];

        $first = $service->placeFromCart($user, $cart, Order::PAYMENT_METHOD_COD, $resolvedAddress, null);
        $second = $service->placeFromCart($user, $cart, Order::PAYMENT_METHOD_COD, $resolvedAddress, null);

        $this->assertTrue($first['ok']);
        $this->assertFalse($second['ok']);
        $this->assertStringContainsString('Giỏ hàng', $second['error']);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 8,
        ]);
    }

    public function test_place_from_cart_fails_when_stock_is_not_enough(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create([
            'quantity' => 1,
            'price' => 100000,
        ]);

        $cart = Cart::query()->create([
            'user_id' => $user->id,
            'coupon_id' => null,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 2,
        ]);

        $service = app(OrderPlacementService::class);
        $resolvedAddress = [
            'shipping_address_snapshot' => '123 Test Street',
            'phone_snapshot' => '0900000000',
            'lat' => 10.762622,
            'lng' => 106.660172,
        ];

        $result = $service->placeFromCart($user, $cart, Order::PAYMENT_METHOD_COD, $resolvedAddress, null);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('không đủ tồn kho', mb_strtolower($result['error']));
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 1,
        ]);
    }
}
