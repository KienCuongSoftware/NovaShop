<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::roots()->with('children.children')->oldest()->paginate(10);
        session(['admin.categories.page' => $categories->currentPage()]);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = Category::roots()->with('children')->orderBy('name')->get();
        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max' => 'Tên danh mục không được quá 255 ký tự.',
            'image.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh không được quá 2MB.',
        ]);

        $data = ['name' => $request->input('name'), 'parent_id' => $request->input('parent_id') ?: null];
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        $page = session('admin.categories.page', 1);
        return redirect()->route('admin.categories.index', ['page' => $page])
                         ->with('success', 'Đã tạo danh mục thành công.');
    }

    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $excludeIds = $category->getDescendantIds(); // không cho chọn chính nó hoặc con làm cha
        $parentCategories = Category::roots()->with('children')->whereNotIn('id', $excludeIds)->orderBy('name')->get();
        return view('admin.categories.edit', compact('category', 'parentCategories', 'excludeIds'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max' => 'Tên danh mục không được quá 255 ký tự.',
            'image.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh không được quá 2MB.',
        ]);

        $parentId = $request->input('parent_id');
        if ($parentId && (int) $parentId === (int) $category->id) {
            return back()->withInput()->withErrors(['parent_id' => 'Danh mục không thể là cha của chính nó.']);
        }
        $data = ['name' => $request->input('name'), 'parent_id' => $parentId ?: null];
        if ($request->hasFile('image')) {
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        $page = session('admin.categories.page', 1);
        return redirect()->route('admin.categories.index', ['page' => $page])
                         ->with('success', 'Đã cập nhật danh mục thành công.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            $page = session('admin.categories.page', 1);
            return redirect()->route('admin.categories.index', ['page' => $page])
                             ->with('error', 'Không thể xóa danh mục có danh mục con. Vui lòng xóa danh mục con trước.');
        }
        if ($category->products()->exists()) {
            $page = session('admin.categories.page', 1);
            return redirect()->route('admin.categories.index', ['page' => $page])
                             ->with('error', 'Không thể xóa danh mục đang có sản phẩm. Vui lòng xóa hoặc chuyển sản phẩm sang danh mục khác trước.');
        }

        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();

        $page = session('admin.categories.page', 1);
        return redirect()->route('admin.categories.index', ['page' => $page])
                         ->with('success', 'Đã xóa danh mục thành công.');
    }
}