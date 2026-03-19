<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $brands = Brand::withCount('products')
            ->when($q !== '', function ($query) use ($q) {
                $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
                $query->where('name', 'like', '%' . $esc . '%')
                    ->orWhere('slug', 'like', '%' . $esc . '%');
            })
            ->orderBy('name')
            ->paginate(7)
            ->withQueryString();
        session(['admin.brands.page' => $brands->currentPage()]);
        return view('admin.brands.index', compact('brands', 'q'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name'),
            ],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên thương hiệu.',
            'name.max' => 'Tên thương hiệu không được quá 255 ký tự.',
            'name.unique' => 'Tên thương hiệu này đã tồn tại.',
            'logo.image' => 'File phải là hình ảnh.',
            'logo.max' => 'Kích thước logo không được quá 2MB.',
        ]);

        $data = ['name' => $request->input('name')];
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        Brand::create($data);

        $page = session('admin.brands.page', 1);
        return redirect()->route('admin.brands.index', ['page' => $page])
            ->with('success', 'Đã tạo thương hiệu thành công.');
    }

    public function show(Brand $brand)
    {
        $brand->loadCount('products');
        return view('admin.brands.show', compact('brand'));
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brand->id),
            ],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên thương hiệu.',
            'name.max' => 'Tên thương hiệu không được quá 255 ký tự.',
            'name.unique' => 'Tên thương hiệu này đã tồn tại.',
            'logo.image' => 'File phải là hình ảnh.',
            'logo.max' => 'Kích thước logo không được quá 2MB.',
        ]);

        $data = ['name' => $request->input('name')];
        if ($request->hasFile('logo')) {
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($data);

        $page = session('admin.brands.page', 1);
        return redirect()->route('admin.brands.index', ['page' => $page])
            ->with('success', 'Đã cập nhật thương hiệu thành công.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            $page = session('admin.brands.page', 1);
            return redirect()->route('admin.brands.index', ['page' => $page])
                ->with('error', 'Không thể xóa thương hiệu đang có sản phẩm. Vui lòng gỡ thương hiệu khỏi sản phẩm trước.');
        }

        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
        }
        $brand->delete();

        $page = session('admin.brands.page', 1);
        return redirect()->route('admin.brands.index', ['page' => $page])
            ->with('success', 'Đã xóa thương hiệu thành công.');
    }
}
