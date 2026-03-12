<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        // Admin không vào được trang welcome — chuyển về trang quản trị
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $products = Product::with('category')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('welcome', compact('products', 'categories', 'categoryId'));
    }

    public function search(Request $request)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        $q = trim((string) $request->input('q', ''));
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $products = Product::with('category')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($q !== '', function ($query) use ($q) {
                $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
                $query->where(function ($qry) use ($esc, $q) {
                    $qry->where('name', 'like', $esc . ' %')
                        ->orWhere('name', 'like', '% ' . $esc . ' %')
                        ->orWhere('name', 'like', '% ' . $esc)
                        ->orWhere('name', $q)
                        ->orWhere(function ($sub) use ($esc, $q) {
                            $sub->whereNotNull('description')
                                ->where(function ($d) use ($esc, $q) {
                                    $d->where('description', 'like', $esc . ' %')
                                        ->orWhere('description', 'like', '% ' . $esc . ' %')
                                        ->orWhere('description', 'like', '% ' . $esc)
                                        ->orWhere('description', $q);
                                });
                        });
                });
            })
            ->oldest()
            ->paginate(12)
            ->withQueryString();

        return view('welcome', compact('products', 'categories', 'q', 'categoryId'));
    }
}
