<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::query()->with('category')->orderByDesc('id')->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.coupons.create', compact('categories'));
    }

    public function store(Request $request)
    {
        Coupon::create($this->validatedData($request));

        return redirect()->route('admin.coupons.index')->with('success', 'Đã tạo mã giảm giá.');
    }

    public function edit(Coupon $coupon)
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.coupons.edit', compact('coupon', 'categories'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validatedData($request, $coupon->id));

        return redirect()->route('admin.coupons.index')->with('success', 'Đã cập nhật mã giảm giá.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Đã xóa mã giảm giá.');
    }

    protected function validatedData(Request $request, ?int $ignoreCouponId = null): array
    {
        $uniqueCode = Rule::unique('coupons', 'code');
        if ($ignoreCouponId) {
            $uniqueCode->ignore($ignoreCouponId);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:64', $uniqueCode],
            'name' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order_amount' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $type = $request->input('discount_type');
        $val = (int) $request->input('discount_value');
        if ($type === Coupon::TYPE_PERCENT && $val > 100) {
            throw ValidationException::withMessages([
                'discount_value' => 'Phần trăm không được vượt quá 100.',
            ]);
        }

        return [
            'code' => strtoupper(trim($request->input('code'))),
            'name' => $request->input('name'),
            'discount_type' => $type,
            'discount_value' => $val,
            'min_order_amount' => (int) $request->input('min_order_amount'),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'starts_at' => $request->input('starts_at') ?: null,
            'ends_at' => $request->input('ends_at') ?: null,
            'max_uses' => $request->filled('max_uses') ? (int) $request->input('max_uses') : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
