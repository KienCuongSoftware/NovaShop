<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use App\Models\CompareItem;
use App\Models\Order;
use App\Models\StockNotificationSubscription;
use App\Models\WishlistItem;
use App\Services\ShippingFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected function isAdminContext(): bool
    {
        return request()->routeIs('admin.*');
    }

    public function index(Request $request)
    {
        if ($this->isAdminContext()) {
            $parentCategories = Category::roots()->orderBy('name')->get();
            $parentCategoryId = $request->filled('parent_category_id') ? (int) $request->input('parent_category_id') : null;

            $q = trim((string) $request->input('q', ''));
            $products = Product::with(['category', 'brand'])
                ->when($parentCategoryId, function ($q) use ($parentCategoryId) {
                    $parent = Category::with('children')->find($parentCategoryId);
                    $ids = $parent ? $parent->getDescendantIds() : [];
                    $q->whereIn('category_id', $ids);
                })
                ->when($q !== '', function ($query) use ($q) {
                    $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
                    $query->where('name', 'like', '%' . $esc . '%');
                })
                ->oldest()
                ->paginate(7)
                ->withQueryString();

            session(['admin.products.page' => $products->currentPage()]);
            return view('admin.products.index', compact('products', 'parentCategories', 'parentCategoryId', 'q'));
        }
        $products = Product::with('category')->oldest()->get();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $parentCategories = Category::roots()->orderBy('name')->get();
        $categoriesByParent = [];
        $categoryToParent = [];
        foreach ($parentCategories as $root) {
            $ids = $root->getDescendantIds();
            $leaves = Category::whereIn('id', $ids)->leaves()->orderBy('name')->get();
            $categoriesByParent[$root->id] = $leaves->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all();
            foreach ($categoriesByParent[$root->id] as $leaf) {
                $categoryToParent[$leaf['id']] = $root->id;
            }
        }
        $brands = Brand::orderBy('name')->get();
        $attributes = Attribute::with('attributeValues')->orderBy('name')->get();
        return view('admin.products.create', compact('parentCategories', 'categoriesByParent', 'categoryToParent', 'brands', 'attributes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'quantity' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục không hợp lệ.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được quá 255 ký tự.',
            'price.required' => 'Vui lòng nhập giá.',
            'price.numeric' => 'Giá phải là số.',
            'price.min' => 'Giá không được âm.',
            'image.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh không được quá 2MB.',
        ]);

        $data = $request->only(['category_id', 'name', 'description', 'price', 'quantity']);
        $data['category_id'] = (int) $request->input('category_id');
        $data['brand_id'] = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;
        $data['is_active'] = $request->boolean('is_active');
        $data['old_price'] = $request->filled('old_price') ? $request->input('old_price') : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        $variants = $request->input('variants', []);
        if (is_array($variants)) {
            foreach ($variants as $i => $v) {
                $attrValues = isset($v['attribute_value']) && is_array($v['attribute_value'])
                    ? array_filter(array_map('intval', $v['attribute_value']))
                    : [];
                $price = isset($v['price']) ? (float) $v['price'] : 0;
                $stock = isset($v['stock']) ? (int) $v['stock'] : 0;
                if (empty($attrValues) && $price <= 0 && $stock <= 0) {
                    continue;
                }
                $variant = $product->variants()->create([
                    'price' => $price,
                    'stock' => $stock,
                    'sku' => isset($v['sku']) && $v['sku'] !== '' ? trim($v['sku']) : null,
                ]);
                $variant->attributeValues()->sync($attrValues);
                if ($request->hasFile("variants.{$i}.image")) {
                    $path = $request->file("variants.{$i}.image")->store('products', 'public');
                    $variant->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
                }
            }
        }

        if ($product->variants()->exists()) {
            $product->update(['quantity' => $product->variants()->sum('stock')]);
            $this->syncProductAttributes($product);
            $this->propagateImageByColor($product);
        }

        $page = session('admin.products.page', 1);
        return redirect()->route('admin.products.index', ['page' => $page])
            ->with('success', 'Đã thêm sản phẩm thành công.');
    }

    /** Đồng bộ product_attributes từ thuộc tính đang dùng trong các variant. */
    private function syncProductAttributes(Product $product): void
    {
        $attributeIds = $product->variants()->with('attributeValues')->get()
            ->flatMap(fn ($v) => $v->attributeValues->pluck('attribute_id'))
            ->unique()
            ->values()
            ->all();
        $product->attributes()->sync($attributeIds);
    }

    /** Trả về attribute_id của thuộc tính "màu" (color) nếu có. */
    private function getColorAttributeId(Product $product): ?int
    {
        $attributes = $product->attributes()->get();
        foreach ($attributes as $attr) {
            $name = strtolower($attr->name ?? '');
            if (in_array($name, ['màu', 'color', 'mau'], true) || str_contains($name, 'màu') || str_contains($name, 'color')) {
                return (int) $attr->id;
            }
        }
        return $attributes->isNotEmpty() ? (int) $attributes->first()->id : null;
    }

    /** Gán ảnh theo màu: cùng màu thì dùng chung ảnh (variant có ảnh chia sẻ cho các variant cùng màu chưa có ảnh). */
    private function propagateImageByColor(Product $product): void
    {
        $colorAttrId = $this->getColorAttributeId($product);
        if ($colorAttrId === null) {
            return;
        }
        $variants = $product->variants()->with(['attributeValues', 'images'])->get();
        $byColor = [];
        foreach ($variants as $v) {
            $colorValueId = $v->attributeValues->firstWhere('attribute_id', $colorAttrId)?->id;
            if ($colorValueId === null) {
                $colorValueId = 'other';
            }
            $byColor[$colorValueId] = $byColor[$colorValueId] ?? [];
            $byColor[$colorValueId][] = $v;
        }
        foreach ($byColor as $variantsInColor) {
            $sourceVariant = null;
            foreach ($variantsInColor as $v) {
                if ($v->images->isNotEmpty()) {
                    $sourceVariant = $v;
                    break;
                }
            }
            if ($sourceVariant === null) {
                continue;
            }
            $path = $sourceVariant->images->first()->image;
            foreach ($variantsInColor as $v) {
                if ($v->id === $sourceVariant->id) {
                    continue;
                }
                if ($v->images->isEmpty()) {
                    $v->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
                }
            }
        }
    }

    /** Xóa ảnh của variant; chỉ xóa file trên đĩa khi không còn variant nào khác dùng chung path. */
    private function deleteVariantImages(ProductVariant $variant): void
    {
        $productId = $variant->product_id;
        foreach ($variant->images as $img) {
            $path = $img->image;
            $img->delete();
            if ($path && ProductImage::where('product_id', $productId)->where('image', $path)->count() === 0) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }

    public function show(Product $product)
    {
        $product->load(['category.parent', 'brand', 'variants.attributeValues.attribute', 'variants.images']);
        return view('admin.products.show', compact('product'));
    }

    /** Trả về view cho người dùng bình thường; lưu hành vi xem để gợi ý. */
    public function show_normal(Product $product, Request $request)
    {
        $product->load(['category.parent.parent', 'brand', 'variants.attributeValues.attribute', 'variants.images']);
        $recentIds = session('recent_product_ids', []);
        $recentIds = array_filter(array_unique(array_merge([$product->id], $recentIds)));
        session(['recent_product_ids' => array_slice($recentIds, 0, 15)]);
        if ($product->category_id) {
            $catIds = session('recent_category_ids', []);
            $catIds = array_filter(array_unique(array_merge([$product->category_id], $catIds)));
            session(['recent_category_ids' => array_slice($catIds, 0, 5)]);
        }
        // Chỉ áp dụng FLASH SALE khi slot đang diễn ra (now nằm trong [start_time, end_time)).
        // Tránh trường hợp hiển thị giá kiểu flash cho "slot kế tiếp" dù người dùng đang không có flash sale.
        $activeFlashSale = \App\Models\FlashSale::active()->with('items')->first();
        $flashItemsByVariantId = [];
        $flashSaleEndTime = null;
        if ($activeFlashSale) {
            $flashSaleEndTime = $activeFlashSale->end_time->toIso8601String();
            foreach ($activeFlashSale->items as $item) {
                $flashItemsByVariantId[$item->product_variant_id] = [
                    'sale_price' => (float) $item->sale_price,
                    'remaining' => $item->remaining,
                ];
            }
        }

        // Reviews: chỉ lấy các review đã được duyệt (is_approved=1).
        // Lưu ý: tách/clone builder để query phân bố không dính sang query lấy danh sách (MySQL only_full_group_by).
        $baseReviewsQuery = \App\Models\ProductReview::query()
            ->where('product_id', $product->id)
            ->where('is_approved', true);

        // Lọc theo số sao (1..5)
        $ratingFilterRaw = $request->query('rating');
        $ratingFilter = in_array((int) $ratingFilterRaw, [1, 2, 3, 4, 5], true) ? (int) $ratingFilterRaw : null;

        $reviewCount = (int) (clone $baseReviewsQuery)->count();
        $avgRating = $reviewCount > 0 ? (float) (clone $baseReviewsQuery)->avg('rating') : 0.0;
        $avgRating = round($avgRating, 1);

        $reviewDistribution = (clone $baseReviewsQuery)
            ->selectRaw('rating, COUNT(*) as cnt')
            ->groupBy('rating')
            ->pluck('cnt', 'rating');

        $reviewsQuery = (clone $baseReviewsQuery);
        if ($ratingFilter !== null) {
            $reviewsQuery->where('rating', $ratingFilter);
        }

        $reviews = $reviewsQuery
            ->with(['user', 'images'])
            ->orderByDesc('created_at')
            ->paginate(10);

        if ($ratingFilter !== null) {
            $reviews->appends(['rating' => $ratingFilter]);
        }

        // Trả về riêng phần review khi frontend gọi AJAX (không reload trang).
        if ($request->boolean('reviews_partial')) {
            $myReview = null;
            if (Auth::check()) {
                $myReview = \App\Models\ProductReview::query()
                    ->where('product_id', $product->id)
                    ->where('user_id', Auth::id())
                    ->with('images')
                    ->first();
            }
            $canReviewProduct = Auth::check()
                && Order::userHasDeliveredPurchase((int) Auth::id(), (int) $product->id);

            return view('products._reviews_block', compact('product', 'reviewCount', 'avgRating', 'reviewDistribution', 'reviews', 'myReview', 'canReviewProduct'));
        }

        $myReview = null;
        if (Auth::check()) {
            $myReview = \App\Models\ProductReview::query()
                ->where('product_id', $product->id)
                ->where('user_id', Auth::id())
                ->with('images')
                ->first();
        }

        $canReviewProduct = Auth::check()
            && Order::userHasDeliveredPurchase((int) Auth::id(), (int) $product->id);

        $rawIds = DB::table('order_items as oi')
            ->join('order_items as oi2', function ($join) {
                $join->on('oi.order_id', '=', 'oi2.order_id')
                    ->whereColumn('oi2.product_id', '!=', 'oi.product_id');
            })
            ->where('oi.product_id', $product->id)
            ->whereNull('oi.deleted_at')
            ->whereNull('oi2.deleted_at')
            ->select('oi2.product_id', DB::raw('COUNT(*) as pair_count'))
            ->groupBy('oi2.product_id')
            ->orderByDesc('pair_count')
            ->limit(8)
            ->pluck('oi2.product_id');
        $order = $rawIds->all();
        $boughtTogetherProducts = collect();
        if (count($order) > 0) {
            $boughtTogetherProducts = Product::query()
                ->whereIn('id', $order)
                ->where('is_active', true)
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $order, true))
                ->values();
        }

        $inWishlist = false;
        $onCompare = false;
        $stockSubscribedVariantIds = collect();
        $stockSubscribedSimple = false;
        if (Auth::check()) {
            $inWishlist = WishlistItem::where('user_id', Auth::id())->where('product_id', $product->id)->exists();
            $onCompare = CompareItem::where('user_id', Auth::id())->where('product_id', $product->id)->exists();
            $subs = StockNotificationSubscription::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->get(['product_variant_id']);
            $stockSubscribedVariantIds = $subs->pluck('product_variant_id')->filter(fn ($id) => $id !== null)->values();
            $stockSubscribedSimple = $subs->contains(fn ($s) => $s->product_variant_id === null);
        }

        $previewKmForEstimate = (float) config('delivery.preview_assumed_km', 15);
        $previewShippingFee = null;
        $previewDistanceKm = null;
        $previewShippingHint = null;
        if (Auth::check()) {
            $addr = Auth::user()->addresses()->orderByDesc('is_default')->orderBy('id')->first();
            if ($addr && $addr->hasCoordinates()) {
                $calc = ShippingFeeService::calculate((float) $addr->lat, (float) $addr->lng);
                $previewShippingFee = $calc['fee'];
                $previewDistanceKm = $calc['distance_km'];
                $previewKmForEstimate = (float) $previewDistanceKm;
            }
        }
        if ($previewShippingFee === null) {
            $calc = ShippingFeeService::calculate(null, null);
            $previewShippingFee = $calc['fee'];
            if ($previewDistanceKm === null) {
                $previewShippingHint = Auth::check()
                    ? 'Thêm tọa độ địa chỉ trên bản đồ để xem phí ship & ngày giao chính xác hơn.'
                    : 'Đăng nhập và lưu địa chỉ giao hàng để ước tính chính xác hơn.';
            }
        }
        [$previewDateFrom, $previewDateTo] = Order::estimatedDeliveryDateRangeFromDistanceKm($previewKmForEstimate, now());
        $productShippingPreview = [
            'fee' => (int) $previewShippingFee,
            'distance_km' => $previewDistanceKm,
            'date_from' => $previewDateFrom,
            'date_to' => $previewDateTo,
            'hint' => $previewShippingHint,
        ];

        return view('products.show', compact(
            'product',
            'activeFlashSale',
            'flashItemsByVariantId',
            'flashSaleEndTime',
            'reviewCount',
            'avgRating',
            'reviewDistribution',
            'reviews',
            'myReview',
            'canReviewProduct',
            'boughtTogetherProducts',
            'inWishlist',
            'onCompare',
            'stockSubscribedVariantIds',
            'stockSubscribedSimple',
            'productShippingPreview'
        ));
    }

    public function edit(Product $product)
    {
        $product->load(['brand', 'variants.attributeValues.attribute', 'variants.images']);
        $parentCategories = Category::roots()->orderBy('name')->get();
        $categoriesByParent = [];
        $categoryToParent = [];
        foreach ($parentCategories as $root) {
            $ids = $root->getDescendantIds();
            $leaves = Category::whereIn('id', $ids)->leaves()->orderBy('name')->get();
            $categoriesByParent[$root->id] = $leaves->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all();
            foreach ($categoriesByParent[$root->id] as $leaf) {
                $categoryToParent[$leaf['id']] = $root->id;
            }
        }
        $brands = Brand::orderBy('name')->get();
        $attributes = $product->attributes()->with('attributeValues')->orderBy('name')->get();
        if ($attributes->isEmpty() && $product->hasVariants()) {
            $this->syncProductAttributes($product);
            $product->load('attributes.attributeValues');
            $attributes = $product->attributes()->with('attributeValues')->orderBy('name')->get();
        }
        if ($attributes->isEmpty()) {
            $attributes = Attribute::with('attributeValues')->orderBy('name')->get();
        }
        return view('admin.products.edit', compact('product', 'parentCategories', 'categoriesByParent', 'categoryToParent', 'brands', 'attributes'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'quantity' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục không hợp lệ.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được quá 255 ký tự.',
            'price.required' => 'Vui lòng nhập giá.',
            'price.numeric' => 'Giá phải là số.',
            'price.min' => 'Giá không được âm.',
            'image.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh không được quá 2MB.',
        ]);

        $data = $request->only(['category_id', 'name', 'description', 'price', 'quantity']);
        $data['brand_id'] = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;
        $data['is_active'] = $request->boolean('is_active');
        $data['old_price'] = $request->filled('old_price') ? $request->input('old_price') : null;

        if ($product->hasVariants()) {
            $data['quantity'] = $product->variants()->sum('stock');
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        $page = session('admin.products.page', 1);
        return redirect()->route('admin.products.index', ['page' => $page])
            ->with('success', 'Đã cập nhật sản phẩm thành công.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        $page = session('admin.products.page', 1);
        return redirect()->route('admin.products.index', ['page' => $page])
            ->with('success', 'Đã xóa sản phẩm thành công.');
    }

    public function storeVariant(Request $request, Product $product)
    {
        $attributeIds = $request->input('attribute_value', []);
        $attributeValueIds = array_filter(array_map('intval', is_array($attributeIds) ? $attributeIds : []));
        sort($attributeValueIds);

        $request->validate([
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'price.required' => 'Vui lòng nhập giá.',
            'stock.required' => 'Vui lòng nhập tồn kho.',
        ]);

        foreach ($product->variants as $v) {
            $existingIds = $v->attributeValues->pluck('id')->sort()->values()->all();
            if ($existingIds === $attributeValueIds) {
                return redirect()->route('admin.products.edit', $product)
                    ->with('error', 'Tổ hợp thuộc tính này đã tồn tại.');
            }
        }

        $variant = $product->variants()->create([
            'price' => $request->input('price'),
            'stock' => (int) $request->input('stock'),
            'sku' => $request->filled('sku') ? trim($request->input('sku')) : null,
        ]);
        $variant->attributeValues()->sync($attributeValueIds);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $variant->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
        }

        $product->update(['quantity' => $product->variants()->sum('stock')]);
        $this->syncProductAttributes($product);
        $this->propagateImageByColor($product);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Đã thêm biến thể.');
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        $request->validate([
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'price.required' => 'Vui lòng nhập giá.',
            'stock.required' => 'Vui lòng nhập tồn kho.',
        ]);

        $variant->update([
            'price' => $request->input('price'),
            'stock' => (int) $request->input('stock'),
            'sku' => $request->filled('sku') ? trim($request->input('sku')) : $variant->sku,
        ]);

        if ($request->hasFile('image')) {
            $this->deleteVariantImages($variant);
            $path = $request->file('image')->store('products', 'public');
            $variant->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
            $this->propagateImageByColor($product);
        }

        $product->update(['quantity' => $product->variants()->sum('stock')]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Đã cập nhật biến thể.');
    }

    public function updateVariantsBulk(Request $request, Product $product)
    {
        $variantsData = $request->input('variants', []);
        if (!is_array($variantsData)) {
            return redirect()->route('admin.products.edit', $product)
                ->with('error', 'Dữ liệu không hợp lệ.');
        }

        $updated = 0;
        foreach ($variantsData as $variantId => $data) {
            $variant = $product->variants()->find($variantId);
            if (!$variant || !is_array($data)) {
                continue;
            }
            $price = isset($data['price']) ? (float) $data['price'] : $variant->price;
            $stock = isset($data['stock']) ? (int) $data['stock'] : $variant->stock;
            $variant->update(['price' => $price, 'stock' => $stock]);

            if ($request->hasFile("variants.{$variantId}.image")) {
                $this->deleteVariantImages($variant);
                $path = $request->file("variants.{$variantId}.image")->store('products', 'public');
                $variant->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
            }
            $updated++;
        }

        $product->update(['quantity' => $product->variants()->sum('stock')]);
        $this->propagateImageByColor($product);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', $updated ? "Đã cập nhật {$updated} biến thể." : 'Không có biến thể nào được cập nhật.');
    }

    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            abort(404);
        }
        $this->deleteVariantImages($variant);
        $variant->delete();
        $product->update(['quantity' => $product->variants()->sum('stock')]);
        $this->syncProductAttributes($product);
        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Đã xóa biến thể.');
    }
}
