<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
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

    public function show(Product $product)
    {
        $product->load(['category.parent', 'brand', 'variants.attributeValues.attribute', 'variants.images']);
        return view('admin.products.show', compact('product'));
    }

    /** Trả về view cho người dùng bình thường; lưu hành vi xem để gợi ý. */
    public function show_normal(Product $product)
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
        return view('products.show', compact('product'));
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
            foreach ($variant->images as $img) {
                if ($img->image && Storage::disk('public')->exists($img->image)) {
                    Storage::disk('public')->delete($img->image);
                }
                $img->delete();
            }
            $path = $request->file('image')->store('products', 'public');
            $variant->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
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
                foreach ($variant->images as $img) {
                    if ($img->image && Storage::disk('public')->exists($img->image)) {
                        Storage::disk('public')->delete($img->image);
                    }
                    $img->delete();
                }
                $path = $request->file("variants.{$variantId}.image")->store('products', 'public');
                $variant->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
            }
            $updated++;
        }

        $product->update(['quantity' => $product->variants()->sum('stock')]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', $updated ? "Đã cập nhật {$updated} biến thể." : 'Không có biến thể nào được cập nhật.');
    }

    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            abort(404);
        }
        foreach ($variant->images as $img) {
            if ($img->image && Storage::disk('public')->exists($img->image)) {
                Storage::disk('public')->delete($img->image);
            }
        }
        $variant->delete();
        $product->update(['quantity' => $product->variants()->sum('stock')]);
        $this->syncProductAttributes($product);
        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Đã xóa biến thể.');
    }
}
