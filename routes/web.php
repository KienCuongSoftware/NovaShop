<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;

// Favicon: Laravel phục vụ trực tiếp để chắc chắn hiển thị trên mọi trang
Route::get('/favicon.ico', function () {
    $path = public_path('favicon.ico');
    if (file_exists($path) && filesize($path) > 0) {
        return response()->file($path, ['Content-Type' => 'image/x-icon', 'Cache-Control' => 'public, max-age=86400']);
    }
    abort(404);
});
Route::get('/favicon.svg', function () {
    $path = public_path('favicon.svg');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=86400']);
});

// Trang chủ welcome (tất cả sản phẩm)
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
// Trang tất cả danh mục
Route::get('/all-categories', [WelcomeController::class, 'allCategories'])->name('all.categories');
// Trang danh sách sản phẩm theo danh mục
Route::get('/categories/{category}', [WelcomeController::class, 'categoryProducts'])->name('category.products');
Route::get('/search', [WelcomeController::class, 'search'])->name('search');

// Xác thực và phân quyền - Route đăng ký và đăng nhập người dùng
Route::get('register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [AuthController::class, 'register']);
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Quản lý tài khoản, giỏ hàng, đặt hàng - cho người dùng đã đăng nhập
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'profileUpdate'])->name('profile.update');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::put('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.place-order');
    Route::get('/order-success/{order}', [OrderController::class, 'success'])->name('order.success');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/paypal/create-order/{order}', [PayPalController::class, 'createOrder'])->name('paypal.create-order');
    Route::get('/paypal/success/{order}', [PayPalController::class, 'success'])->name('paypal.success');
});

// Route dành cho admin (sử dụng middleware để kiểm tra quyền)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    // URL cũ /admin/products/update/{id}: GET → redirect sang edit, POST → gọi update (tránh MethodNotAllowedHttpException)
    Route::get('/admin/products/update/{id}', function (int $id) {
        $product = \App\Models\Product::findOrFail($id);
        return redirect()->route('admin.products.edit', $product, 301);
    })->name('admin.products.update.redirect');
    Route::post('/admin/products/update/{id}', function (Illuminate\Http\Request $request, int $id) {
        $product = \App\Models\Product::findOrFail($id);
        return app(ProductController::class)->update($request, $product);
    })->name('admin.products.update.post');
    // Đặt tiền tố 'admin' cho tất cả các route của products
    Route::resource('/admin/products', ProductController::class, ['as' => 'admin']);
    Route::post('/admin/products/{product}/variants', [ProductController::class, 'storeVariant'])->name('admin.products.variants.store');
    Route::put('/admin/products/{product}/variants/{variant}', [ProductController::class, 'updateVariant'])->name('admin.products.variants.update');
    Route::put('/admin/products/{product}/variants-bulk', [ProductController::class, 'updateVariantsBulk'])->name('admin.products.variants.bulk');
    Route::delete('/admin/products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('admin.products.variants.destroy');
    // Đặt tiền tố 'admin' cho tất cả các route của categories
    Route::resource('/admin/categories', CategoryController::class, ['as' => 'admin']);
    Route::resource('/admin/brands', BrandController::class, ['as' => 'admin']);
    Route::resource('/admin/users', UserController::class, ['as' => 'admin']);
    Route::resource('/admin/attributes', AttributeController::class, ['as' => 'admin'])->except(['show']);
    Route::post('/admin/attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('admin.attributes.values.store');
    Route::delete('/admin/attributes/{attribute}/values/{attributeValue}', [AttributeController::class, 'destroyValue'])->name('admin.attributes.values.destroy');
});

// Serve product images from storage (with cache; mime by extension to avoid 500 on .webp/Windows)
Route::get('/images/products/{filename}', function (string $filename) {
    $path = 'products/' . $filename;
    if (!Storage::disk('public')->exists($path)) {
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
    $path = 'brands/' . $filename;
    if (!Storage::disk('public')->exists($path)) {
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
    $path = 'categories/' . $filename;
    if (!Storage::disk('public')->exists($path)) {
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
    $path = 'avatars/' . $filename;
    if (!Storage::disk('public')->exists($path)) {
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

// Route cho người dùng bình thường (đã đăng nhập)
Route::middleware(['auth'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show_normal'])->name('products.show');
});
