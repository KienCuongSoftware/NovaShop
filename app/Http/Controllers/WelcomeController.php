<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    /** Gợi ý sản phẩm tương tự dựa trên hành vi xem chi tiết (cùng danh mục với sản phẩm đã xem). */
    protected function getSuggestedProducts(): \Illuminate\Support\Collection
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
            ->limit(9)
            ->get();
    }

    /**
     * Trang chủ: hiển thị tất cả sản phẩm (không lọc danh mục).
     */
    public function index(Request $request)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        $products = $this->buildProductQuery(null, $request)->paginate(12)->withQueryString();
        $suggestedProducts = $this->getSuggestedProducts();
        $currentSort = $this->getSortParam($request);
        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;

        return view('welcome', compact('products', 'categories', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax'));
    }

    /**
     * Trang tất cả danh mục.
     */
    public function allCategories()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        return view('all-categories', compact('categories'));
    }

    /**
     * Trang danh sách sản phẩm theo danh mục.
     */
    public function categoryProducts(Request $request, Category $category)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        $products = $this->buildProductQuery($category->id, $request)->paginate(12)->withQueryString();
        $suggestedProducts = $this->getSuggestedProducts();
        $currentSort = $this->getSortParam($request);
        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;

        return view('welcome', compact('products', 'categories', 'category', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax'));
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
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
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
            });

        $products = $this->applyPriceFilter($products, $request);
        $products = $this->applySort($products, $request)->paginate(12)->withQueryString();
        $suggestedProducts = $this->getSuggestedProducts();
        $currentSort = $this->getSortParam($request);
        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;

        return view('welcome', compact('products', 'categories', 'q', 'categoryId', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax'));
    }

    /** Lấy tham số sort từ request. */
    protected function getSortParam(Request $request): string
    {
        $sort = trim((string) $request->input('sort', ''));
        $allowed = ['popular', 'newest', 'bestselling', 'price_asc', 'price_desc'];
        return in_array($sort, $allowed) ? $sort : 'popular';
    }

    /** Xây query sản phẩm với filter danh mục, giá và sort. */
    protected function buildProductQuery(?int $categoryId, Request $request)
    {
        $query = Product::with('category')
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId));
        $query = $this->applyPriceFilter($query, $request);
        return $this->applySort($query, $request);
    }

    /** Áp dụng lọc theo khoảng giá. */
    protected function applyPriceFilter($query, Request $request)
    {
        $min = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $max = $request->filled('price_max') ? (float) $request->input('price_max') : null;
        if ($min !== null && $min > 0) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null && $max > 0) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    /** Áp dụng sắp xếp theo tham số sort. */
    protected function applySort($query, Request $request)
    {
        $sort = $this->getSortParam($request);
        return match ($sort) {
            'newest' => $query->latest(),
            'bestselling' => $query->orderByDesc('quantity'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->inRandomOrder(),
        };
    }
}
