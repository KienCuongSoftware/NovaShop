<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return redirect()->route('categories.index');
});

// Serve product images from storage (avoids 403 with symlink on Windows/some environments)
Route::get('/images/products/{filename}', function (string $filename) {
    $path = 'products/' . $filename;
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $file = Storage::disk('public')->get($path);
    $mime = Storage::disk('public')->mimeType($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '[a-zA-Z0-9._-]+')->name('storage.products.image');

Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
