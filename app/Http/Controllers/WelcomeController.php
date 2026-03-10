<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function index()
    {
        // Admin không vào được trang welcome — chuyển về trang quản trị
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $products = Product::with('category')->latest()->paginate(12);
        return view('welcome', compact('products'));
    }
}
