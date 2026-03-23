<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Middleware\AddWeakEtagPublicApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — JSON for SPA / mobile (Sanctum-ready)
|--------------------------------------------------------------------------
| Base URL: /api/v1/...
*/

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.login');

    Route::middleware([AddWeakEtagPublicApi::class])->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('api.v1.categories.index');
        Route::get('/products', [ProductController::class, 'index'])->name('api.v1.products.index');
        Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('api.v1.products.show');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        Route::get('/user', function (Request $request) {
            $user = $request->user();

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
            ]);
        })->name('api.v1.user');

        Route::get('/addresses', [AddressController::class, 'index'])->name('api.v1.addresses.index');

        Route::get('/cart', [CartController::class, 'show'])->name('api.v1.cart.show');
        Route::post('/cart/items', [CartController::class, 'addItem'])->name('api.v1.cart.items.store');
        Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('api.v1.cart.items.update');
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem'])->name('api.v1.cart.items.destroy');
        Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('api.v1.cart.coupon.apply');
        Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('api.v1.cart.coupon.remove');

        Route::get('/checkout/shipping-fee', [CheckoutController::class, 'shippingFee'])->name('api.v1.checkout.shipping-fee');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('api.v1.checkout.store');
    });
});
