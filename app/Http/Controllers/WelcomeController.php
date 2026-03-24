<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    /** Gợi ý sản phẩm tương tự dựa trên hành vi xem chi tiết (cùng danh mục với sản phẩm đã xem). */
    protected function getSuggestedProducts(): \Illuminate\Support\Collection
    {
        $excludeIds = session('recent_product_ids', []);
        $categoryIds = session('recent_category_ids', []);

        $max = 20;
        $step = 4;

        $chosenIds = collect($excludeIds)->filter()->values();

        $baseQuery = Product::with('category')->when(! $categoryIds || empty($categoryIds), function ($q) {
            // Nếu chưa có lịch sử danh mục thì lấy random toàn site.
        }, function ($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds);
        });

        if (! empty($chosenIds->all())) {
            $baseQuery->whereNotIn('id', $chosenIds->all());
        }

        $products = $baseQuery->inRandomOrder()->limit($max)->get();
        $products = $products->values();

        // Nếu chưa đủ 4 món để render ít nhất 1 hàng -> bù thêm từ pool random toàn site.
        if ($products->count() < $step) {
            $toNeed = $step - $products->count();
            $more = Product::with('category')
                ->when(! $categoryIds || empty($categoryIds), function ($q) {
                    // Không làm gì thêm
                }, function ($q) use ($categoryIds) {
                    // Cố gắng lấy tiếp theo filter đang có, nếu không đủ thì fallback ở bước sau.
                    $q->whereIn('category_id', $categoryIds);
                })
                ->whereNotIn('id', array_values(array_unique(array_merge($excludeIds, $products->pluck('id')->all()))))
                ->inRandomOrder()
                ->limit($toNeed)
                ->get();

            $products = $products->merge($more)->unique('id')->values();
        }

        // Luôn trả về số lượng là bội của 4: 4, 8, 12, 16, 20...
        $desiredCount = (int) ceil(max($step, $products->count()) / $step) * $step;
        $desiredCount = min($desiredCount, $max);

        if ($products->count() < $desiredCount) {
            $needed = $desiredCount - $products->count();
            $more = Product::with('category')
                // Fallback: bỏ filter category khi không đủ để bù tiếp.
                ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
                ->whereNotIn('id', $products->pluck('id')->all())
                ->inRandomOrder()
                ->limit($needed)
                ->get();

            $products = $products->merge($more)->unique('id')->values();
        }

        return $products->take($desiredCount);
    }

    /**
     * Trang chủ: hiển thị tất cả sản phẩm (không lọc danh mục).
     */
    public function index(Request $request)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = CatalogCache::rootCategoryTree();
        $products = $this->buildProductQuery(null, $request)->paginate(12)->withQueryString();
        $suggestedProducts = $this->getSuggestedProducts();
        $currentSort = $this->getSortParam($request);
        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;

        $activeCategoryIds = [];
        $showSidebarAndFilter = false;
        ['activeFlashSale' => $activeFlashSale, 'todaySlots' => $todaySlots] = CatalogCache::flashSaleWelcomeContext();

        return view('welcome', compact('products', 'categories', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax', 'activeCategoryIds', 'showSidebarAndFilter', 'activeFlashSale', 'todaySlots'));
    }

    /**
     * Trang tất cả danh mục.
     */
    public function allCategories()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = CatalogCache::rootCategoryTree();

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

        $categories = CatalogCache::rootCategoryTree();
        $sidebarCategories = $category->children()->with('children')->orderBy('name')->get();
        $sidebarParent = $category->parent;

        $categoryIds = $category->getDescendantIds();
        $categoryBrands = Brand::whereHas('products', fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->orderBy('name')
            ->get();

        $products = $this->buildProductQuery($category->id, $request)->paginate(12)->withQueryString();
        $suggestedProducts = $this->getSuggestedProducts();
        $currentSort = $this->getSortParam($request);
        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;
        $brandSlug = $request->filled('brand') ? trim((string) $request->input('brand')) : null;

        $activeCategoryIds = array_map(fn ($c) => $c->id, $category->getBreadcrumbPath());
        $showSidebarAndFilter = true;
        ['activeFlashSale' => $activeFlashSale, 'todaySlots' => $todaySlots] = CatalogCache::flashSaleWelcomeContext();

        return view('welcome', compact('products', 'categories', 'category', 'sidebarCategories', 'sidebarParent', 'categoryBrands', 'brandSlug', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax', 'activeCategoryIds', 'showSidebarAndFilter', 'activeFlashSale', 'todaySlots'));
    }

    public function search(Request $request)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->input('q', ''));
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $productsQuery = Product::query()
            ->when($categoryId, function ($query) use ($categoryId) {
                $category = Category::with('children')->find($categoryId);
                $ids = $category ? $category->getDescendantIds() : [$categoryId];
                $query->whereIn('category_id', $ids);
            })
            ->when($q !== '', function ($query) use ($q) {
                $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
                $pattern = '%'.$esc.'%';
                $query->where('name', 'like', $pattern);
            });
        $productsQuery = $this->applyPriceFilter($productsQuery, $request);

        $resultCategoryIds = (clone $productsQuery)->distinct()->pluck('category_id')->filter()->values()->all();
        $categories = $this->buildSearchSidebarCategories($resultCategoryIds);

        $products = $productsQuery->with('category');
        $products = $this->applySort($products, $request)->paginate(12)->withQueryString();

        // Update session để gợi ý theo hành vi tìm kiếm
        // (lấy các sản phẩm thuộc trang kết quả đầu tiên)
        $foundProducts = $products->getCollection();
        $foundProductIds = $foundProducts->pluck('id')->all();
        $foundCategoryIds = $foundProducts->pluck('category_id')->filter()->all();

        $recentIds = session('recent_product_ids', []);
        $recentIds = array_values(array_filter(array_unique(array_merge($foundProductIds, $recentIds))));
        session(['recent_product_ids' => array_slice($recentIds, 0, 15)]);

        $recentCatIds = session('recent_category_ids', []);
        $recentCatIds = array_values(array_filter(array_unique(array_merge($foundCategoryIds, $recentCatIds))));
        session(['recent_category_ids' => array_slice($recentCatIds, 0, 5)]);

        $suggestedProducts = $this->getSuggestedProducts();
        $currentSort = $this->getSortParam($request);
        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;

        $activeCategoryIds = $categoryId ? array_map(fn ($c) => $c->id, optional(Category::find($categoryId))->getBreadcrumbPath() ?? []) : [];
        $showSidebarAndFilter = true;
        ['activeFlashSale' => $activeFlashSale, 'todaySlots' => $todaySlots] = CatalogCache::flashSaleWelcomeContext();

        return view('welcome', compact('products', 'categories', 'q', 'categoryId', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax', 'activeCategoryIds', 'showSidebarAndFilter', 'activeFlashSale', 'todaySlots'));
    }

    /** Sidebar tìm kiếm: chỉ danh mục cha–con trực tiếp của sản phẩm tìm được. */
    protected function buildSearchSidebarCategories(array $resultCategoryIds): \Illuminate\Support\Collection
    {
        if (empty($resultCategoryIds)) {
            return collect();
        }
        $showCategoryIds = [];
        foreach ($resultCategoryIds as $cid) {
            $cat = Category::find($cid);
            if (! $cat) {
                continue;
            }
            foreach ($cat->getBreadcrumbPath() as $c) {
                $showCategoryIds[$c->id] = true;
            }
            $showCategoryIds[$cat->id] = true;
        }
        $showCategoryIds = array_keys($showCategoryIds);

        $rootIds = [];
        foreach ($showCategoryIds as $cid) {
            $cat = Category::find($cid);
            while ($cat && $cat->parent_id !== null) {
                $cat = $cat->parent;
            }
            if ($cat) {
                $rootIds[$cat->id] = true;
            }
        }
        $rootIds = array_keys($rootIds);
        if (empty($rootIds)) {
            return collect();
        }

        $roots = Category::whereIn('id', $rootIds)
            ->with(['children' => function ($query) {
                $query->with(['children' => function ($query2) {
                    $query2->with('children');
                }]);
            }])
            ->orderBy('name')
            ->get();

        return $roots->map(function ($root) use ($showCategoryIds) {
            return $this->filterCategoryBranch($root, $showCategoryIds);
        })->filter()->values();
    }

    /** Chỉ giữ nhánh danh mục có chứa ít nhất một id trong $showCategoryIds. */
    protected function filterCategoryBranch(Category $category, array $showCategoryIds): ?Category
    {
        $includeSelf = in_array($category->id, $showCategoryIds);
        $filteredChildren = collect($category->children ?? [])
            ->map(fn ($child) => $this->filterCategoryBranch($child, $showCategoryIds))
            ->filter()
            ->values();
        if ($includeSelf || $filteredChildren->isNotEmpty()) {
            $category->setRelation('children', $filteredChildren);

            return $category;
        }

        return null;
    }

    /** Lấy tham số sort từ request. */
    protected function getSortParam(Request $request): string
    {
        $sort = trim((string) $request->input('sort', ''));
        $allowed = ['popular', 'newest', 'bestselling', 'price_asc', 'price_desc'];

        return in_array($sort, $allowed) ? $sort : 'popular';
    }

    /** Xây query sản phẩm với filter danh mục, brand, giá và sort. */
    protected function buildProductQuery(?int $categoryId, Request $request)
    {
        $query = Product::with(['category', 'brand'])
            ->when($categoryId !== null, function ($q) use ($categoryId) {
                $category = Category::with('children')->find($categoryId);
                $ids = $category ? $category->getDescendantIds() : [$categoryId];
                $q->whereIn('category_id', $ids);
            })
            ->when(true, function ($q) use ($request) {
                $brandId = null;
                if ($request->filled('brand')) {
                    $brand = Brand::where('slug', trim((string) $request->input('brand')))->first();
                    $brandId = $brand?->id;
                } elseif ($request->filled('brand_id')) {
                    $brandId = (int) $request->input('brand_id');
                }
                if ($brandId !== null) {
                    $q->where('brand_id', $brandId);
                }
            });
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
