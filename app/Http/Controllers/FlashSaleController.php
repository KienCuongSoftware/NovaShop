<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index()
    {
        $flashSales = FlashSale::withCount('items')
            ->with(['items.productVariant:id,product_id'])
            ->orderByDesc('start_time')
            ->paginate(7)
            ->withQueryString();

        // Hiển thị đúng: items_count = số biến thể, products_count = số sản phẩm (unique product_id)
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
            'end_time' => 'required|date|after:start_time',
            'status' => 'nullable|in:active,ended,scheduled',
        ], [
            'name.required' => 'Vui lòng nhập tên chương trình.',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ]);

        $flashSale = FlashSale::create([
            'name' => $request->input('name'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'status' => $request->input('status', FlashSale::STATUS_ACTIVE),
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
            'status' => 'nullable|in:active,ended,scheduled',
        ]);

        $flash_sale->update([
            'name' => $request->input('name'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'status' => $request->input('status', $flash_sale->status),
        ]);

        return back()->with('success', 'Đã cập nhật chương trình.');
    }

    public function destroy(FlashSale $flash_sale)
    {
        $flash_sale->delete();
        $page = session('admin.flash_sales.page', 1);
        return redirect()->route('admin.flash-sales.index', ['page' => $page])->with('success', 'Đã xóa chương trình Flash Sale.');
    }

    /** Thêm biến thể vào flash sale (AJAX hoặc form). */
    public function storeItem(Request $request, FlashSale $flash_sale)
    {
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'sale_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::findOrFail($request->input('product_variant_id'));
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

    /** Cập nhật item (giá, số lượng). */
    public function updateItem(Request $request, FlashSale $flash_sale, FlashSaleItem $item)
    {
        if ($item->flash_sale_id != $flash_sale->id) {
            abort(404);
        }
        $request->validate([
            'sale_price' => 'required|numeric|min:0',
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

    /** Xóa item khỏi flash sale. */
    public function destroyItem(FlashSale $flash_sale, FlashSaleItem $item)
    {
        if ($item->flash_sale_id != $flash_sale->id) {
            abort(404);
        }
        $item->delete();
        return back()->with('success', 'Đã xóa sản phẩm khỏi Flash Sale.');
    }
}
