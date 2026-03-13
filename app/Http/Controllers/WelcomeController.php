<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

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

    /**
     * Lấy nhãn từ ảnh qua Google Vision API.
     *
     * @return array<string> Các nhãn (labels) mô tả nội dung ảnh
     */
    protected function getImageLabelsFromVision(string $imagePath): array
    {
        $apiKey = config('services.google_vision.api_key');
        if (empty($apiKey)) {
            return [];
        }

        $imageData = base64_encode(Storage::disk('public')->get($imagePath));

        $response = Http::timeout(10)->post(
            'https://vision.googleapis.com/v1/images:annotate?key=' . $apiKey,
            [
                'requests' => [
                    [
                        'image' => ['content' => $imageData],
                        'features' => [
                            ['type' => 'LABEL_DETECTION', 'maxResults' => 15],
                            ['type' => 'WEB_DETECTION', 'maxResults' => 5],
                        ],
                    ],
                ],
            ]
        );

        if (!$response->successful()) {
            return [];
        }

        $labels = [];
        $body = $response->json();

        if (!empty($body['responses'][0]['labelAnnotations'])) {
            foreach ($body['responses'][0]['labelAnnotations'] as $ann) {
                $desc = $ann['description'] ?? '';
                if ($desc && strlen($desc) >= 2) {
                    $labels[] = strtolower($desc);
                }
            }
        }

        if (!empty($body['responses'][0]['webDetection']['webEntities'])) {
            foreach ($body['responses'][0]['webDetection']['webEntities'] as $entity) {
                $desc = $entity['description'] ?? '';
                if ($desc && strlen($desc) >= 2 && !in_array(strtolower($desc), $labels)) {
                    $labels[] = strtolower($desc);
                }
            }
        }

        return array_slice(array_unique($labels), 0, 12);
    }

    /**
     * Tìm kiếm sản phẩm bằng hình ảnh (Google Vision API + perceptual hash fallback).
     * Hỗ trợ GET cho phân trang (dùng session lưu kết quả).
     */
    public function searchByImage(Request $request)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $categories = Category::orderBy('name')->get();
        $suggestedProducts = $this->getSuggestedProducts();
        $currentSort = 'popular';
        $priceMin = null;
        $priceMax = null;
        $q = '';
        $categoryId = null;

        // GET: phân trang từ session (user click page 2, 3...)
        if ($request->isMethod('get')) {
            $productIds = session('search_image_product_ids', []);
            if (empty($productIds)) {
                return redirect()->route('welcome');
            }
            $idsStr = implode(',', array_map('intval', $productIds));
            $products = Product::with('category')
                ->whereIn('id', $productIds)
                ->orderByRaw("FIELD(id, {$idsStr})")
                ->paginate(12)
                ->withQueryString();
            return view('welcome', compact('products', 'categories', 'q', 'categoryId', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax'))
                ->with('imageSearchMessage', 'Kết quả tìm kiếm theo hình ảnh');
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'image.required' => 'Vui lòng chọn hình ảnh để tìm kiếm.',
            'image.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh không được quá 5MB.',
        ]);

        $uploadedPath = null;

        try {
            $uploadedPath = $request->file('image')->store('temp', 'public');
            $fullPath = Storage::disk('public')->path($uploadedPath);

            $productIds = [];

            $labels = $this->getImageLabelsFromVision($uploadedPath);
            if (!empty($labels)) {
                $productIds = Product::with('category')
                    ->where(function ($query) use ($labels) {
                        foreach ($labels as $label) {
                            $esc = str_replace(['%', '_'], ['\\%', '\\_'], $label);
                            $query->orWhere(function ($q) use ($esc) {
                                $q->where('name', 'like', '%' . $esc . '%')
                                    ->orWhere('description', 'like', '%' . $esc . '%');
                            });
                        }
                    })
                    ->pluck('id')
                    ->unique()
                    ->values()
                    ->all();
            }

            if (empty($productIds)) {
                $hasher = new ImageHash(new DifferenceHash(16));
                $queryHash = $hasher->hash($fullPath);

                $productsWithImages = Product::with('category')
                    ->whereNotNull('image')
                    ->where('image', '!=', '')
                    ->get();

                $scored = [];
                foreach ($productsWithImages as $product) {
                    $productPath = Storage::disk('public')->path($product->image);
                    if (!file_exists($productPath)) {
                        continue;
                    }
                    try {
                        $productHash = $hasher->hash($productPath);
                        $distance = $hasher->distance($queryHash, $productHash);
                        $scored[] = ['product' => $product, 'distance' => $distance];
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                usort($scored, fn ($a, $b) => $a['distance'] <=> $b['distance']);
                $productIds = array_map(fn ($s) => $s['product']->id, $scored);
            }

            if ($uploadedPath) {
                $oldPath = session('searched_image_path');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                session(['searched_image_path' => $uploadedPath]);
            }

            session(['search_image_product_ids' => $productIds]);

            if (empty($productIds)) {
                $products = Product::with('category')->where('is_active', true)->inRandomOrder()->paginate(12);
            } else {
                $idsStr = implode(',', array_map('intval', $productIds));
                $products = Product::with('category')
                    ->whereIn('id', $productIds)
                    ->orderByRaw("FIELD(id, {$idsStr})")
                    ->paginate(12)
                    ->withQueryString();
            }
        } catch (\Throwable $e) {
            session()->forget('search_image_product_ids');
            $products = Product::with('category')->where('is_active', true)->inRandomOrder()->paginate(12);
        }

        return view('welcome', compact('products', 'categories', 'q', 'categoryId', 'suggestedProducts', 'currentSort', 'priceMin', 'priceMax'))
            ->with('imageSearchMessage', 'Kết quả tìm kiếm theo hình ảnh');
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
