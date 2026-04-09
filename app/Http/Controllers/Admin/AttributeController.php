<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::withCount('attributeValues')
            ->orderBy('name')
            ->paginate(7)
            ->withQueryString();

        session(['admin.attributes.page' => $attributes->currentPage()]);
        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('attributes', 'name')],
        ], [
            'name.required' => 'Vui lòng nhập tên thuộc tính.',
            'name.unique' => 'Thuộc tính này đã tồn tại.',
        ]);
        Attribute::create(['name' => trim($request->input('name'))]);
        $page = session('admin.attributes.page', 1);
        return redirect()->route('admin.attributes.index', ['page' => $page])->with('success', 'Đã thêm thuộc tính.');
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load('attributeValues');
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('attributes', 'name')->ignore($attribute->id)],
        ], [
            'name.required' => 'Vui lòng nhập tên thuộc tính.',
            'name.unique' => 'Thuộc tính này đã tồn tại.',
        ]);
        $attribute->update(['name' => trim($request->input('name'))]);
        $page = session('admin.attributes.page', 1);
        return redirect()->route('admin.attributes.index', ['page' => $page])->with('success', 'Đã cập nhật thuộc tính.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        $page = session('admin.attributes.page', 1);
        return redirect()->route('admin.attributes.index', ['page' => $page])->with('success', 'Đã xóa thuộc tính.');
    }

    public function storeValue(Request $request, Attribute $attribute)
    {
        $request->validate([
            'value' => ['required', 'string', 'max:255'],
        ], [
            'value.required' => 'Vui lòng nhập giá trị.',
        ]);
        $value = trim($request->input('value'));
        $existing = AttributeValue::where('attribute_id', $attribute->id)->where('value', $value)->first();
        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json(['id' => $existing->id, 'value' => $existing->value]);
            }
            return redirect()->route('admin.attributes.edit', $attribute)
                ->with('error', 'Giá trị "' . $value . '" đã tồn tại.');
        }
        $av = $attribute->attributeValues()->create(['value' => $value]);
        if ($request->wantsJson()) {
            return response()->json(['id' => $av->id, 'value' => $av->value]);
        }
        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Đã thêm giá trị.');
    }

    public function destroyValue(Attribute $attribute, AttributeValue $attributeValue)
    {
        if ($attributeValue->attribute_id !== $attribute->id) {
            abort(404);
        }
        $attributeValue->delete();
        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Đã xóa giá trị.');
    }
}
