<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Services\CartPricingService;
use App\Services\CouponService;
use App\Services\RecommendationEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product.category', 'items.productVariant', 'coupon'])->first();
        if (!$cart) {
            $cart = $user->cart()->create();
            $cart->load(['items.product.category', 'items.productVariant', 'coupon']);
        }
        $activeFlashSale = \App\Models\FlashSale::active()->with('items')->first();
        $flashByVariant = $activeFlashSale?->items->keyBy('product_variant_id');
        $cartSubtotal = (int) round(CartPricingService::cartSubtotal($cart, $flashByVariant));
        $couponDiscount = 0;
        $couponError = null;
        if ($cart->coupon_id && $cart->coupon) {
            $couponResult = app(CouponService::class)->validateAndComputeDiscount($user, $cart, $cart->coupon);
            if ($couponResult['ok']) {
                $couponDiscount = $couponResult['discount'];
            } else {
                $couponError = $couponResult['message'];
                $cart->update(['coupon_id' => null]);
                $cart->refresh();
            }
        }
        $totalAfterCoupon = max(0, $cartSubtotal - $couponDiscount);

        return view('user.cart.index', compact('cart', 'activeFlashSale', 'cartSubtotal', 'couponDiscount', 'totalAfterCoupon', 'couponError'));
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string|max:64'], ['code.required' => 'Vui lòng nhập mã giảm giá.']);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->firstOrCreate([]);
        $cart->load(['items.product.category', 'items.productVariant']);

        $code = strtoupper(trim($request->input('code')));
        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            return back()->with('error', 'Mã giảm giá không tồn tại.');
        }

        $cart->coupon()->associate($coupon);
        $cart->save();

        $result = app(CouponService::class)->validateAndComputeDiscount($user, $cart, $coupon);
        if (!$result['ok']) {
            $cart->update(['coupon_id' => null]);

            return back()->with('error', $result['message']);
        }

        return back()->with('success', 'Đã áp dụng mã giảm giá.');
    }

    public function removeCoupon()
    {
        $cart = Auth::user()->cart;
        if ($cart) {
            $cart->update(['coupon_id' => null]);
        }

        return back()->with('success', 'Đã bỏ mã giảm giá.');
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);
        $productId = (int) $request->input('product_id');
        $recSource = trim((string) $request->input('rec_src', $request->query('rec_src', '')));
        $recVariant = trim((string) $request->input('rec_variant', $request->query('rec_variant', '')));
        $variantId = $request->filled('product_variant_id') ? (int) $request->input('product_variant_id') : null;
        $quantity = max(1, (int) ($request->input('quantity') ?? 1));

        $product = Product::with('variants')->findOrFail($productId);

        if ($product->hasVariants()) {
            if (!$variantId) {
                return back()->with('error', 'Vui lòng chọn biến thể (Size/Màu) sản phẩm.');
            }
            $variant = $product->variants()->find($variantId);
            if (!$variant) {
                return back()->with('error', 'Biến thể không hợp lệ.');
            }
            $availableQty = $variant->stock;
            $flashItem = FlashSaleItem::activeForVariant($variantId);
            if ($flashItem !== null) {
                $availableQty = min($availableQty, $flashItem->remaining);
            }
        } else {
            if ($variantId) {
                return back()->with('error', 'Sản phẩm không có biến thể.');
            }
            $availableQty = (int) $product->quantity;
        }

        if ($quantity > $availableQty) {
            return back()->with('error', 'Số lượng vượt quá tồn kho.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->firstOrCreate([]);
        $item = $cart->items()
            ->where('product_id', $productId)
            ->where(fn ($q) => $variantId ? $q->where('product_variant_id', $variantId) : $q->whereNull('product_variant_id'))
            ->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;
            $maxQty = $product->hasVariants() ? $product->variants()->find($item->product_variant_id)?->stock : $product->quantity;
            $flashItem = $item->product_variant_id ? FlashSaleItem::activeForVariant($item->product_variant_id) : null;
            if ($flashItem) {
                $maxQty = min($maxQty, $flashItem->remaining);
            }
            if ($newQty > $maxQty) {
                return back()->with('error', 'Số lượng vượt quá tồn kho.');
            }
            $item->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        if ($recSource === 'suggested') {
            $variant = $recVariant !== '' ? $recVariant : (string) session('rec_ab_variant', 'v1');
            app(RecommendationEventLogger::class)->logAddToCart(
                $user,
                $productId,
                $variant,
                ['surface' => 'welcome_suggested']
            );
            $recCartVariants = session('rec_cart_variants', []);
            $recCartVariants[$productId] = $variant;
            session(['rec_cart_variants' => $recCartVariants]);
        }

        return back()->with('success', 'Đã thêm vào giỏ hàng.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:1',
        ]);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart;
        if (!$cart) {
            return back()->with('error', 'Giỏ hàng trống.');
        }
        $item = $cart->items()->with('productVariant')->findOrFail($request->input('cart_item_id'));
        $quantity = (int) $request->input('quantity');
        $maxQty = $item->productVariant ? $item->productVariant->stock : (int) $item->product->quantity;
        $flashItem = $item->product_variant_id ? FlashSaleItem::activeForVariant($item->product_variant_id) : null;
        if ($flashItem) {
            $maxQty = min($maxQty, $flashItem->remaining);
        }
        if ($quantity > $maxQty) {
            return back()->with('error', 'Số lượng vượt quá tồn kho.');
        }
        $item->update(['quantity' => $quantity]);
        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function remove(CartItem $cartItem)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart;
        if (!$cart || $cartItem->cart_id !== $cart->id) {
            abort(403);
        }
        $cartItem->delete();
        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
}
