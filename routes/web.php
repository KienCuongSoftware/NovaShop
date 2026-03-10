<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WelcomeController;

// Trang chủ welcome
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Xác thực và phân quyền - Route đăng ký và đăng nhập người dùng
Route::get('register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [AuthController::class, 'register']);
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Route dành cho admin (sử dụng middleware để kiểm tra quyền)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    // Đặt tiền tố 'admin' cho tất cả các route của products
    Route::resource('/admin/products', ProductController::class, ['as' => 'admin']);
    // Đặt tiền tố 'admin' cho tất cả các route của categories
    Route::resource('/admin/categories', CategoryController::class, ['as' => 'admin']);
});

// Serve product images from storage
Route::get('/images/products/{filename}', function (string $filename) {
    $path = 'products/' . $filename;
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $file = Storage::disk('public')->get($path);
    $fullPath = Storage::disk('public')->path($path);
    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '[a-zA-Z0-9._-]+')->name('storage.products.image');

// Route cho người dùng bình thường (đã đăng nhập)
Route::middleware(['auth'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show_normal'])->name('products.show');
});
