<?php

namespace Tests\Feature;

use App\Mail\OrderStatusChangedMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderPlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderCancelSendsMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cancel_order_sends_status_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create(['quantity' => 5, 'price' => 100000]);

        $cart = Cart::query()->create(['user_id' => $user->id, 'coupon_id' => null]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
        ]);

        $service = app(OrderPlacementService::class);
        $resolvedAddress = [
            'shipping_address_snapshot' => '1 Test St',
            'phone_snapshot' => '0900000000',
            'lat' => 10.762622,
            'lng' => 106.660172,
        ];
        $placed = $service->placeFromCart($user, $cart, Order::PAYMENT_METHOD_COD, $resolvedAddress, null);
        $this->assertTrue($placed['ok']);
        Mail::assertSent(OrderStatusChangedMail::class, 1);

        Mail::fake();

        /** @var Order $order */
        $order = $placed['order'];
        $this->actingAs($user)->post(route('orders.cancel', $order));

        Mail::assertSent(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) use ($order) {
            return $mail->order->id === $order->id
                && $mail->currentStatus === Order::STATUS_CANCELLED
                && $mail->previousStatus === Order::STATUS_PENDING;
        });
    }
}
