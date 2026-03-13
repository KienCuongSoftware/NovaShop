<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    /** Gợi ý sản phẩm tương tự dựa trên hành vi xem chi tiết (cùng danh mục với sản phẩm đã xem). */
    protected function getSuggestedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $excludeIds = session('recent_product_ids', []);
        $categoryIds = session('recent_category_ids', []);

        if (empty($categoryIds)) {
            return collect();
        }

        return Product::with('category')
            ->whereIn('category_id', $categoryIds)
            ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->inRandomOrder()
            ->limit(8)
            ->get();
    }

    /**
     * Trang chủ: hiển thị tất cả sản phẩm (không lọc danh mục).
     */
    public function index()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        $products = Product::with('category')->inRandomOrder()->paginate(12);
        $suggestedProducts = $this->getSuggestedProducts();

        return view('welcome', compact('products', 'categories', 'suggestedProducts'));
    }

    /**
     * Trang danh sách sản phẩm theo danh mục (giống Shopee).
     */
    public function categoryProducts(Category $category)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        $products = Product::with('category')
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(12);
        $suggestedProducts = $this->getSuggestedProducts();

        return view('welcome', compact('products', 'categories', 'category', 'suggestedProducts'));
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
            ->inRandomOrder()
            ->paginate(12)
            ->withQueryString();

        $suggestedProducts = $this->getSuggestedProducts();
        return view('welcome', compact('products', 'categories', 'q', 'categoryId', 'suggestedProducts'));
    }
}
