<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\User\AiChatController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\InitialsAvatarController;
use App\Http\Controllers\User\ListSharePublicController;
use App\Http\Controllers\User\ProductController as UserProductController;
use App\Http\Controllers\User\ProductReviewController;
use App\Http\Controllers\User\SearchSuggestionController;
use App\Http\Controllers\User\WelcomeController;
use App\Services\CatalogCache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Favicon: Laravel phục vụ trực tiếp để chắc chắn hiển thị trên mọi trang
Route::get('/favicon.ico', function () {
    $path = public_path('favicon.ico');
    if (file_exists($path) && filesize($path) > 0) {
        return response()->file($path, ['Content-Type' => 'image/x-icon', 'Cache-Control' => 'public, max-age=86400']);
    }
    abort(404);
});
// Avatar chữ cái (SVG, cache app + Cache-Control; ?v= bắn lại khi đổi tên/màu)
Route::get('/avatars/initial/{user}', InitialsAvatarController::class)
    ->middleware('throttle:120,1')
    ->name('avatars.initials');

Route::get('/favicon.svg', function () {
    $path = public_path('favicon.svg');
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=86400']);
});

// API Flash Sale: slot hiện tại/tiếp theo + danh sách slot trong ngày (cho countdown reload khi hết giờ)
Route::get('/api/flash-sale', function () {
    ['activeFlashSale' => $current, 'todaySlots' => $slots] = CatalogCache::flashSaleWelcomeContext();
    if (! $current) {
        return response()->json(['current' => null, 'slots' => $slots->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'start_time' => $s->start_time->toIso8601String(),
            'end_time' => $s->end_time->toIso8601String(),
        ])]);
    }
    $now = now();
    $isActive = $current->start_time <= $now && $current->end_time > $now;

    return response()->json([
        'current' => [
            'id' => $current->id,
            'name' => $current->name,
            'start_time' => $current->start_time->toIso8601String(),
            'end_time' => $current->end_time->toIso8601String(),
            'is_active' => $isActive,
            'items' => $current->items->map(function ($item) {
                $v = $item->productVariant;
                $p = $v ? $v->product : null;
                $originalPrice = $v ? (float) $v->price : 0;
                $discountPct = $originalPrice > 0 ? round((1 - (float) $item->sale_price / $originalPrice) * 100) : 0;

                return [
                    'id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'sale_price' => (int) $item->sale_price,
                    'quantity' => $item->quantity,
                    'sold' => $item->sold,
                    'remaining' => $item->remaining,
                    'discount_percent' => $discountPct,
                    'product' => $p ? [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                        'image' => $p->image,
                        'url' => route('products.show', $p),
                    ] : null,
                    'variant' => $v ? ['id' => $v->id, 'price' => (int) $v->price] : null,
                ];
            })->values()->all(),
        ],
        'slots' => $slots->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'start_time' => $s->start_time->toIso8601String(),
            'end_time' => $s->end_time->toIso8601String(),
        ])->values()->all(),
    ]);
})->name('api.flash-sale');

// Trang chủ welcome (tất cả sản phẩm)
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
// Trang tất cả danh mục
Route::get('/all-categories', [WelcomeController::class, 'allCategories'])->name('all.categories');
// Trang danh sách sản phẩm theo danh mục
Route::get('/categories/{category}', [WelcomeController::class, 'categoryProducts'])->name('category.products');
Route::get('/search', [WelcomeController::class, 'search'])->name('search');
Route::get('/search/suggestions', [SearchSuggestionController::class, 'suggestions'])
    ->middleware('throttle:60,1')
    ->name('search.suggestions');

// Trợ lý AI (OpenAI — khóa API chỉ dùng phía server)
Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
Route::middleware(['throttle:20,1'])->group(function () {
    Route::post('/ai-chat/send', [AiChatController::class, 'send'])->name('ai-chat.send');
    Route::post('/ai-chat/clear', [AiChatController::class, 'clear'])->name('ai-chat.clear');
});

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/ai-chat/history', [AiChatController::class, 'history'])->name('ai-chat.history');
});

// Xác thực và phân quyền - Route đăng ký và đăng nhập người dùng
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
});
Route::get('register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [AuthController::class, 'register']);
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify-otp', [AuthController::class, 'showVerifyOtpForm'])->name('verification.otp.notice');
    Route::post('/email/verify-otp', [AuthController::class, 'verifyOtp'])->name('verification.otp.verify');
    Route::post('/email/verify-otp/resend', [AuthController::class, 'resendOtp'])->name('verification.otp.resend');
});

// Quản lý tài khoản, giỏ hàng, đặt hàng - cho người dùng đã đăng nhập
Route::namespace('App\Http\Controllers\User')->middleware(['auth', 'email.verified.otp'])->group(function () {
    Route::get('/profile', 'ProfileController@profile')->name('profile');
    Route::put('/profile', 'ProfileController@profileUpdate')->name('profile.update');
    Route::get('/addresses', 'AddressController@index')->name('addresses.index');
    Route::get('/addresses/create', 'AddressController@create')->name('addresses.create');
    Route::post('/addresses', 'AddressController@store')->name('addresses.store');
    Route::get('/addresses/{address}/edit', 'AddressController@edit')->name('addresses.edit');
    Route::put('/addresses/{address}', 'AddressController@update')->name('addresses.update');
    Route::delete('/addresses/{address}', 'AddressController@destroy')->name('addresses.destroy');
    Route::post('/addresses/{address}/default', 'AddressController@setDefault')->name('addresses.set-default');
    Route::get('/cart', 'CartController@index')->name('cart.index');
    Route::post('/cart/add', 'CartController@add')->name('cart.add');
    Route::put('/cart/update', 'CartController@update')->name('cart.update');
    Route::delete('/cart/remove/{cartItem}', 'CartController@remove')->name('cart.remove');
    Route::post('/cart/coupon', 'CartController@applyCoupon')->name('cart.coupon.apply');
    Route::delete('/cart/coupon', 'CartController@removeCoupon')->name('cart.coupon.remove');
    Route::get('/wishlist', 'WishlistController@index')->name('wishlist.index');
    Route::post('/wishlist/toggle', 'WishlistController@toggle')->name('wishlist.toggle');
    Route::post('/wishlist/share', 'WishlistController@share')->name('wishlist.share');
    Route::delete('/wishlist/{product}', 'WishlistController@remove')->name('wishlist.remove');
    Route::get('/compare', 'CompareController@index')->name('compare.index');
    Route::post('/compare/add', 'CompareController@add')->name('compare.add');
    Route::post('/compare/share', 'CompareController@share')->name('compare.share');
    Route::delete('/compare/{product}', 'CompareController@remove')->name('compare.remove');
    Route::post('/compare/clear', 'CompareController@clear')->name('compare.clear');
    Route::post('/stock-notifications', 'StockNotificationController@store')->name('stock-notifications.store');
    Route::delete('/stock-notifications/{product}', 'StockNotificationController@destroy')->name('stock-notifications.destroy');
    Route::get('/notifications/stock', 'StockAlertInboxController@index')->name('stock-alerts.index');
    Route::get('/checkout', 'CheckoutController@show')->name('checkout.show');
    Route::get('/checkout/shipping-fee', 'CheckoutController@shippingFee')->name('checkout.shipping-fee');
    Route::post('/checkout/place-order', 'CheckoutController@placeOrder')->name('checkout.place-order');
    Route::get('/order-success/{order}', 'OrderController@success')->name('order.success');
    Route::get('/orders', 'OrderController@index')->name('orders.index');
    Route::get('/orders/{order}', 'OrderController@show')->name('orders.show');
    Route::post('/orders/{order}/cancel', 'OrderController@cancel')->name('orders.cancel');
    Route::post('/orders/{order}/request-return', 'OrderController@requestReturn')->name('orders.request-return');
    Route::get('/paypal/create-order/{order}', 'PayPalController@createOrder')->name('paypal.create-order');
    Route::get('/paypal/success/{order}', 'PayPalController@success')->name('paypal.success');
    Route::get('/momo/create-order/{order}', 'MomoController@createOrder')->name('momo.create-order');
    Route::get('/momo/return/{order}', 'MomoController@handleReturn')->name('momo.return');
});

Route::post('/momo/ipn', 'App\Http\Controllers\User\MomoController@ipn')->name('momo.ipn');

// Route dành cho admin (sử dụng middleware để kiểm tra quyền)
Route::middleware(['auth', 'email.verified.otp', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    // Dashboard
    Route::namespace('App\Http\Controllers\Admin')->group(function () {
        Route::get('/dashboard', 'DashboardController@dashboard')->name('dashboard');
    });

    // Products module
    Route::prefix('')->group(function () {
        // Sửa lỗi gõ nhầm: /admin/procucts/... → /admin/products/...
        Route::get('/procucts/{path}', function (string $path) {
            return redirect('/admin/products/'.$path, 301);
        })->where('path', '.*');

        // URL cũ /admin/products/update/{id}: GET → redirect sang edit, POST → gọi update
        Route::get('/products/update/{id}', function (int $id) {
            $product = \App\Models\Product::findOrFail($id);

            return redirect()->route('admin.products.edit', $product, 301);
        })->name('products.update.redirect');
        Route::post('/products/update/{id}', function (Illuminate\Http\Request $request, int $id) {
            $product = \App\Models\Product::findOrFail($id);

            return app(AdminProductController::class)->update($request, $product);
        })->name('products.update.post');

        Route::resource('/products', AdminProductController::class);
        Route::post('/products/{product}/variants', [AdminProductController::class, 'storeVariant'])->name('products.variants.store');
        Route::put('/products/{product}/variants/{variant}', [AdminProductController::class, 'updateVariant'])->name('products.variants.update');
        Route::get('/products/{product}/variants-bulk', function (\App\Models\Product $product) {
            return redirect()->route('admin.products.edit', $product, 302);
        })->name('products.variants.bulk.redirect');
        Route::put('/products/{product}/variants-bulk', [AdminProductController::class, 'updateVariantsBulk'])->name('products.variants.bulk');
        Route::delete('/products/{product}/variants/{variant}', [AdminProductController::class, 'destroyVariant'])->name('products.variants.destroy');
    });

    // Catalog modules
    Route::prefix('')->group(function () {
        Route::resource('/categories', CategoryController::class);
        Route::resource('/brands', BrandController::class);
        Route::resource('/attributes', AttributeController::class)->except(['show']);
        Route::post('/attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');
        Route::delete('/attributes/{attribute}/values/{attributeValue}', [AttributeController::class, 'destroyValue'])->name('attributes.values.destroy');
        Route::resource('/flash-sales', FlashSaleController::class);
        Route::post('/flash-sales/{flash_sale}/items', [FlashSaleController::class, 'storeItem'])->name('flash_sales.items.store');
        Route::put('/flash-sales/{flash_sale}/items/{item}', [FlashSaleController::class, 'updateItem'])->name('flash_sales.items.update');
        Route::delete('/flash-sales/{flash_sale}/items/{item}', [FlashSaleController::class, 'destroyItem'])->name('flash_sales.items.destroy');
    });

    // User/admin management modules
    Route::namespace('App\Http\Controllers\Admin')->group(function () {
        Route::post('/users/{user}/toggle-block', 'UserController@toggleBlocked')->name('users.toggle-block');
        Route::resource('/users', 'UserController')->except(['destroy']);
        Route::resource('/coupons', 'CouponController', ['parameters' => ['coupons' => 'coupon']]);
        Route::resource('/search-synonyms', 'SearchSynonymController', ['parameters' => ['search-synonyms' => 'search_synonym']]);
        Route::get('/profile', 'ProfileController@edit')->name('profile.edit');
        Route::put('/profile', 'ProfileController@update')->name('profile.update');
    });

    // Orders + inventory module
    Route::namespace('App\Http\Controllers\Admin')->group(function () {
        Route::get('/orders', 'OrderController@index')->name('orders.index');
        Route::get('/orders/{order}', 'OrderController@show')->name('orders.show');
        Route::put('/orders/{order}/status', 'OrderController@updateStatus')->name('orders.update-status');
        Route::get('/inventory-logs', 'InventoryLogController@index')->name('inventory-logs.index');
    });

    // Review moderation module
    Route::namespace('App\Http\Controllers\Admin')->group(function () {
        Route::get('/product-reviews', 'ProductReviewController@index')->name('product-reviews.index');
        Route::post('/product-reviews/{review}/approve', 'ProductReviewController@approve')->name('product-reviews.approve');
        Route::post('/product-reviews/{review}/reject', 'ProductReviewController@reject')->name('product-reviews.reject');
    });
});

// Serve product images from storage (with cache; mime by extension to avoid 500 on .webp/Windows)
Route::get('/images/products/{filename}', function (string $filename) {
    $path = 'products/'.$filename;
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeMap = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $file = Storage::disk('public')->get($path);

    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '[a-zA-Z0-9._-]+')->name('storage.products.image');

// Serve brand logos from storage/app/public/brands
Route::get('/images/brands/{filename}', function (string $filename) {
    $path = 'brands/'.$filename;
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeMap = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $file = Storage::disk('public')->get($path);

    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '[a-zA-Z0-9._-]+')->name('storage.brands.image');

// Serve category images from storage/app/public/categories
Route::get('/images/categories/{filename}', function (string $filename) {
    $path = 'categories/'.$filename;
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeMap = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $file = Storage::disk('public')->get($path);

    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '[a-zA-Z0-9._-]+')->name('storage.categories.image');

// Serve user avatars from storage/app/public/avatars
Route::get('/images/avatars/{filename}', function (string $filename) {
    $path = 'avatars/'.$filename;
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeMap = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $file = Storage::disk('public')->get($path);

    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '[a-zA-Z0-9._-]+')->name('storage.avatars.image');

// Serve review images from storage/app/public/review_images
Route::get('/images/reviews/{filename}', function (string $filename) {
    $path = 'review_images/'.$filename;
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeMap = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $file = Storage::disk('public')->get($path);

    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '[a-zA-Z0-9._-]+')->name('storage.reviews.image');

// Chuyển hướng URL cũ/sai /product/show sang trang chủ (route đúng là /products/{slug})
Route::get('/product/show', function () {
    return redirect()->route('welcome', [], 301);
})->name('product.show.redirect');

// Public share links for wishlist/compare (read-only)
Route::get('/share/wishlist/{token}', [ListSharePublicController::class, 'showWishlist'])
    ->name('share.wishlist.show');
Route::get('/share/compare/{token}', [ListSharePublicController::class, 'showCompare'])
    ->name('share.compare.show');

// Route cho người dùng bình thường (đã đăng nhập)
Route::middleware(['auth', 'email.verified.otp'])->group(function () {
    Route::get('/products', [UserProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [UserProductController::class, 'show'])->name('products.show');

    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])
        ->name('products.reviews.store');
});
