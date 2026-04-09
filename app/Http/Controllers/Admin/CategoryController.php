<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $categories = Category::roots()
            ->when($q !== '', function ($query) use ($q) {
                $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
                $query->where('name', 'like', '%' . $esc . '%');
            })
            ->oldest()
            ->paginate(7)
            ->withQueryString();

        session(['admin.categories.page' => $categories->currentPage()]);
        return view('admin.categories.index', compact('categories', 'q'));
    }

    public function create()
    {
        $parentCategories = Category::roots()->with('children')->orderBy('name')->get();
        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $parentId = $request->input('parent_id') ?: null;
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($q) use ($parentId) {
                    if ($parentId === null) {
                        $q->whereNull('parent_id');
                    } else {
                        $q->where('parent_id', $parentId);
                    }
                }),
            ],
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max' => 'Tên danh mục không được quá 255 ký tự.',
            'name.unique' => 'Đã có danh mục trùng tên trong cùng cấp.',
            'image.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh không được quá 2MB.',
        ]);

        $data = ['name' => $request->input('name'), 'parent_id' => $parentId];
        if ($parentId === null && $request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        $page = session('admin.categories.page', 1);
        return redirect()->route('admin.categories.index', ['page' => $page])
                         ->with('success', 'Đã tạo danh mục thành công.');
    }

    public function show(Category $category)
    {
        $category->load(['parent', 'children.children']);
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $category->load(['children.children']);
        $excludeIds = $category->getDescendantIds();
        $parentCategories = Category::roots()->with('children')->whereNotIn('id', $excludeIds)->orderBy('name')->get();
        return view('admin.categories.edit', compact('category', 'parentCategories', 'excludeIds'));
    }

    public function update(Request $request, Category $category)
    {
        $parentId = $request->input('parent_id') ?: null;
        if ($parentId && (int) $parentId === (int) $category->id) {
            return back()->withInput()->withErrors(['parent_id' => 'Danh mục không thể là cha của chính nó.']);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(function ($q) use ($parentId) {
                        if ($parentId === null) {
                            $q->whereNull('parent_id');
                        } else {
                            $q->where('parent_id', $parentId);
                        }
                    }),
            ],
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max' => 'Tên danh mục không được quá 255 ký tự.',
            'name.unique' => 'Đã có danh mục trùng tên trong cùng cấp.',
            'image.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh không được quá 2MB.',
        ]);

        $data = ['name' => $request->input('name'), 'parent_id' => $parentId];
        if ($parentId !== null) {
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = null;
        } elseif ($request->hasFile('image')) {
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        if ($category->isRoot()) {
            $descendantIds = $category->getDescendantIds();
            $allowedExistingIds = array_values(array_filter($descendantIds, fn ($id) => (int) $id !== (int) $category->id));

            $deleteIds = array_filter(array_map('intval', (array) $request->input('delete_ids', [])));
            if (!empty($deleteIds)) {
                $deleteIds = array_values(array_intersect($deleteIds, $allowedExistingIds));
                if (!empty($deleteIds)) {
                    $toDelete = Category::whereIn('id', $deleteIds)->get();
                    foreach ($toDelete as $c) {
                        if ($c->children()->exists() || $c->products()->exists()) {
                            continue;
                        }
                        if ($c->image && Storage::disk('public')->exists($c->image)) {
                            Storage::disk('public')->delete($c->image);
                        }
                        $c->delete();
                    }
                }
            }

            $childrenInput = (array) $request->input('children', []);
            $updateIds = array_filter(array_map('intval', array_keys($childrenInput)));
            $updateIds = array_values(array_intersect($updateIds, $allowedExistingIds));
            if (!empty($updateIds)) {
                $cats = Category::whereIn('id', $updateIds)->get()->keyBy('id');
                foreach ($updateIds as $id) {
                    $cat = $cats->get($id);
                    if (!$cat) {
                        continue;
                    }
                    $name = trim((string) ($childrenInput[$id]['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    if (mb_strlen($name, 'UTF-8') > 255) {
                        $name = mb_substr($name, 0, 255, 'UTF-8');
                    }
                    $dup = Category::query()
                        ->where('parent_id', $cat->parent_id)
                        ->where('name', $name)
                        ->where('id', '!=', $cat->id)
                        ->exists();
                    if ($dup) {
                        continue;
                    }
                    $cat->update(['name' => $name]);
                }
            }

            $newItems = (array) $request->input('new_children', []);
            if (!empty($newItems)) {
                $level1Ids = $category->children()->pluck('id')->map(fn ($v) => (int) $v)->all();
                $allowedParentIds = array_merge([(int) $category->id], $level1Ids);

                foreach ($newItems as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $name = trim((string) ($row['name'] ?? ''));
                    $pid = isset($row['parent_id']) && $row['parent_id'] !== '' ? (int) $row['parent_id'] : (int) $category->id;
                    if ($name === '' || !in_array($pid, $allowedParentIds, true)) {
                        continue;
                    }
                    if (mb_strlen($name, 'UTF-8') > 255) {
                        $name = mb_substr($name, 0, 255, 'UTF-8');
                    }
                    $dup = Category::query()->where('parent_id', $pid)->where('name', $name)->exists();
                    if ($dup) {
                        continue;
                    }
                    Category::create(['name' => $name, 'parent_id' => $pid]);
                }
            }
        }

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
