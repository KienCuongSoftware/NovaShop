<?php

namespace App\Http\Controllers;

use App\Models\Product;
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

            $products = Product::with(['category', 'brand'])
                ->when($parentCategoryId, function ($q) use ($parentCategoryId) {
                    $parent = Category::with('children')->find($parentCategoryId);
                    $ids = $parent ? $parent->getDescendantIds() : [];
                    $q->whereIn('category_id', $ids);
                })
                ->oldest()
                ->paginate(7)
                ->withQueryString();

            session(['admin.products.page' => $products->currentPage()]);
            return view('admin.products.index', compact('products', 'parentCategories', 'parentCategoryId'));
        }
        $products = Product::with('category')->oldest()->get();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::leaves()->with('parent.parent')->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        return view('admin.products.create', compact('categories', 'brands'));
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

        Product::create($data);

        $page = session('admin.products.page', 1);
        return redirect()->route('admin.products.index', ['page' => $page])
            ->with('success', 'Đã thêm sản phẩm thành công.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand']);
        return view('admin.products.show', compact('product'));
    }

    /** Trả về view cho người dùng bình thường; lưu hành vi xem để gợi ý. */
    public function show_normal(Product $product)
    {
        $product->load(['category.parent.parent', 'brand']);
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
        $product->load('brand');
        $categories = Category::leaves()->with('parent.parent')->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories', 'brands'));
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
}
