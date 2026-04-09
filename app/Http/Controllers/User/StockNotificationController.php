<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockNotificationSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockNotificationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $product = Product::with('variants')->findOrFail((int) $request->input('product_id'));
        $variantId = $request->filled('product_variant_id') ? (int) $request->input('product_variant_id') : null;

        if ($variantId !== null) {
            $variant = ProductVariant::where('product_id', $product->id)->findOrFail($variantId);
            if ((int) $variant->stock > 0) {
                return back()->with('error', 'Biến thể này đang còn hàng.');
            }
        } else {
            if ($product->hasVariants()) {
                return back()->with('error', 'Vui lòng chọn biến thể hết hàng để đăng ký thông báo.');
            }
            if ((int) $product->quantity > 0) {
                return back()->with('error', 'Sản phẩm đang còn hàng.');
            }
        }

        StockNotificationSubscription::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
            ],
            [
                'email' => Auth::user()->email,
                'notified_at' => null,
                'seen_at' => null,
            ]
        );

        return back()->with('success', 'Đã đăng ký: chúng tôi sẽ gửi email khi có hàng lại.');
    }

    public function destroy(Product $product, Request $request)
    {
        $variantId = $request->query('variant_id');
        $variantId = $variantId !== null && $variantId !== '' ? (int) $variantId : null;

        StockNotificationSubscription::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->when($variantId !== null, fn ($q) => $q->where('product_variant_id', $variantId))
            ->when($variantId === null, fn ($q) => $q->whereNull('product_variant_id'))
            ->delete();

        return back()->with('success', 'Đã hủy đăng ký thông báo.');
    }
}
