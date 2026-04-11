<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FlashSaleController extends Controller
{
    public function index()
    {
        $flashSales = FlashSale::withCount('items')
            ->with(['items.productVariant:id,product_id'])
            ->orderByDesc('start_time')
            ->paginate(7)
            ->withQueryString();

        $flashSales->getCollection()->transform(function ($fs) {
            $productIds = $fs->items->pluck('productVariant.product_id')->filter()->unique();
            $fs->products_count = $productIds->count();
            return $fs;
        });
        session(['admin.flash_sales.page' => $flashSales->currentPage()]);
        return view('admin.flash_sales.index', compact('flashSales'));
    }

    public function create()
    {
        return view('admin.flash_sales.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
        ], [
            'name.required' => 'Vui lòng nhập tên chương trình.',
        ]);

        $start = Carbon::parse($request->input('start_time'))->startOfMinute();
        $end = Carbon::parse($request->input('end_time'))->startOfMinute();
        $now = now()->startOfMinute();

        if ($start->lt($now)) {
            throw ValidationException::withMessages([
                'start_time' => 'Thời gian bắt đầu không được là quá khứ.',
            ]);
        }
        if (! $end->gt($start)) {
            throw ValidationException::withMessages([
                'end_time' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            ]);
        }
        if ($end->lte($now)) {
            throw ValidationException::withMessages([
                'end_time' => 'Thời gian kết thúc phải sau thời điểm hiện tại.',
            ]);
        }

        $flashSale = FlashSale::create([
            'name' => $request->input('name'),
            'start_time' => $start,
            'end_time' => $end,
            'status' => FlashSale::computeStatus($start, $end),
        ]);

        return redirect()->route('admin.flash-sales.edit', $flashSale)
            ->with('success', 'Đã tạo chương trình Flash Sale. Thêm sản phẩm vào chương trình.');
    }

    public function show(FlashSale $flash_sale)
    {
        $flash_sale->load(['items.productVariant.product']);
        return view('admin.flash_sales.show', compact('flash_sale'));
    }

    public function edit(FlashSale $flash_sale)
    {
        $flash_sale->load(['items.productVariant.product', 'items.productVariant.attributeValues.attribute']);
        $addedVariantIds = $flash_sale->items->pluck('product_variant_id')->all();
        $variantsForSelect = ProductVariant::with('product')
            ->whereNotIn('id', $addedVariantIds)
            ->get()
            ->groupBy('product_id');
        return view('admin.flash_sales.edit', compact('flash_sale', 'variantsForSelect'));
    }

    public function update(Request $request, FlashSale $flash_sale)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
        ]);

        $start = Carbon::parse($request->input('start_time'))->startOfMinute();
        $end = Carbon::parse($request->input('end_time'))->startOfMinute();
        $now = now()->startOfMinute();
        $origStart = $flash_sale->start_time->copy()->startOfMinute();
        $origEnd = $flash_sale->end_time->copy()->startOfMinute();

        if ($start->lt($now) && ! $start->equalTo($origStart)) {
            throw ValidationException::withMessages([
                'start_time' => 'Thời gian bắt đầu không được đặt về quá khứ.',
            ]);
        }
        if (! $end->gt($start)) {
            throw ValidationException::withMessages([
                'end_time' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            ]);
        }
        if ($end->lte($now) && ! $end->equalTo($origEnd)) {
            throw ValidationException::withMessages([
                'end_time' => 'Thời gian kết thúc phải sau thời điểm hiện tại (hoặc giữ nguyên nếu chương trình đã kết thúc).',
            ]);
        }

        $flash_sale->update([
            'name' => $request->input('name'),
            'start_time' => $start,
            'end_time' => $end,
            'status' => FlashSale::computeStatus($start, $end),
        ]);

        return back()->with('success', 'Đã cập nhật chương trình.');
    }

    public function destroy(FlashSale $flash_sale)
    {
        $flash_sale->delete();
        $page = session('admin.flash_sales.page', 1);
        return redirect()->route('admin.flash-sales.index', ['page' => $page])->with('success', 'Đã xóa chương trình Flash Sale.');
    }

    public function storeItem(Request $request, FlashSale $flash_sale)
    {
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'sale_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::findOrFail($request->input('product_variant_id'));
        if ((float) $request->input('sale_price') > (float) $variant->price) {
            throw ValidationException::withMessages([
                'sale_price' => 'Giá Flash Sale không được lớn hơn giá gốc biến thể ('.number_format((float) $variant->price, 0, ',', '.').'₫).',
            ]);
        }
        $exists = FlashSaleItem::where('flash_sale_id', $flash_sale->id)
            ->where('product_variant_id', $variant->id)->exists();
        if ($exists) {
            return back()->with('error', 'Biến thể này đã có trong chương trình.');
        }

        FlashSaleItem::create([
            'flash_sale_id' => $flash_sale->id,
            'product_variant_id' => $variant->id,
            'sale_price' => $request->input('sale_price'),
            'quantity' => (int) $request->input('quantity'),
            'sold' => 0,
        ]);

        return back()->with('success', 'Đã thêm sản phẩm vào Flash Sale.');
    }

    public function updateItem(Request $request, FlashSale $flash_sale, FlashSaleItem $item)
    {
        if ($item->flash_sale_id != $flash_sale->id) {
            abort(404);
        }
        $variant = $item->productVariant;
        if (! $variant) {
            return back()->with('error', 'Không tìm thấy biến thể sản phẩm.');
        }

        $request->validate([
            'sale_price' => ['required', 'numeric', 'min:0', function (string $attribute, mixed $value, \Closure $fail) use ($variant) {
                if ((float) $value > (float) $variant->price) {
                    $fail('Giá Flash Sale không được lớn hơn giá gốc biến thể ('.number_format((float) $variant->price, 0, ',', '.').'₫).');
                }
            }],
            'quantity' => 'required|integer|min:0',
        ]);
        $quantity = (int) $request->input('quantity');
        if ($quantity < $item->sold) {
            return back()->with('error', 'Số lượng không được nhỏ hơn đã bán (' . $item->sold . ').');
        }
        $item->update([
            'sale_price' => $request->input('sale_price'),
            'quantity' => $quantity,
        ]);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroyItem(FlashSale $flash_sale, FlashSaleItem $item)
    {
        if ($item->flash_sale_id != $flash_sale->id) {
            abort(404);
        }
        $item->delete();
        return back()->with('success', 'Đã xóa sản phẩm khỏi Flash Sale.');
    }
}
