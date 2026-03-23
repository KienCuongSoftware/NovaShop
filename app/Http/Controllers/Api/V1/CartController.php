<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartItemResource;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Services\CartPricingService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);
        $payload = $this->buildCartPayload($request, $cart);

        return response()->json($payload);
    }

    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);
        $productId = (int) $request->input('product_id');
        $variantId = $request->filled('product_variant_id') ? (int) $request->input('product_variant_id') : null;
        $quantity = max(1, (int) ($request->input('quantity') ?? 1));

        $product = Product::with('variants')->findOrFail($productId);

        if ($product->hasVariants()) {
            if (! $variantId) {
                return response()->json(['message' => 'Vui lòng chọn biến thể (Size/Màu) sản phẩm.'], 422);
            }
            $variant = $product->variants()->find($variantId);
            if (! $variant) {
                return response()->json(['message' => 'Biến thể không hợp lệ.'], 422);
            }
            $availableQty = $variant->stock;
            $flashItem = FlashSaleItem::activeForVariant($variantId);
            if ($flashItem !== null) {
                $availableQty = min($availableQty, $flashItem->remaining);
            }
        } else {
            if ($variantId) {
                return response()->json(['message' => 'Sản phẩm không có biến thể.'], 422);
            }
            $availableQty = (int) $product->quantity;
        }

        if ($quantity > $availableQty) {
            return response()->json(['message' => 'Số lượng vượt quá tồn kho.'], 422);
        }

        $user = $request->user();
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
                return response()->json(['message' => 'Số lượng vượt quá tồn kho.'], 422);
            }
            $item->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        $cart->refresh()->load(['items.product', 'items.productVariant.attributeValues.attribute', 'coupon']);

        return response()->json($this->buildCartPayload($request, $cart), 201);
    }

    public function updateItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->getCart($request);
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        $quantity = (int) $request->input('quantity');
        $cartItem->load('productVariant', 'product');
        $maxQty = $cartItem->productVariant ? $cartItem->productVariant->stock : (int) $cartItem->product->quantity;
        $flashItem = $cartItem->product_variant_id ? FlashSaleItem::activeForVariant($cartItem->product_variant_id) : null;
        if ($flashItem) {
            $maxQty = min($maxQty, $flashItem->remaining);
        }
        if ($quantity > $maxQty) {
            return response()->json(['message' => 'Số lượng vượt quá tồn kho.'], 422);
        }
        $cartItem->update(['quantity' => $quantity]);
        $cart->refresh()->load(['items.product', 'items.productVariant.attributeValues.attribute', 'coupon']);

        return response()->json($this->buildCartPayload($request, $cart));
    }

    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->getCart($request);
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }
        $cartItem->delete();
        $cart->refresh()->load(['items.product', 'items.productVariant.attributeValues.attribute', 'coupon']);

        return response()->json($this->buildCartPayload($request, $cart));
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:64']);
        $cart = $this->getCart($request);
        $cart->load(['items.product.category', 'items.productVariant.attributeValues.attribute']);

        $code = strtoupper(trim($request->input('code')));
        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon) {
            return response()->json(['message' => 'Mã giảm giá không tồn tại.'], 422);
        }

        $cart->coupon()->associate($coupon);
        $cart->save();

        $result = app(CouponService::class)->validateAndComputeDiscount($cart, $coupon);
        if (! $result['ok']) {
            $cart->update(['coupon_id' => null]);

            return response()->json(['message' => $result['message']], 422);
        }

        $cart->refresh()->load(['items.product', 'items.productVariant.attributeValues.attribute', 'coupon']);

        return response()->json($this->buildCartPayload($request, $cart));
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);
        $cart->update(['coupon_id' => null]);
        $cart->refresh()->load(['items.product', 'items.productVariant.attributeValues.attribute', 'coupon']);

        return response()->json($this->buildCartPayload($request, $cart));
    }

    protected function getCart(Request $request): \App\Models\Cart
    {
        $user = $request->user();
        $cart = $user->cart()->with(['items.product', 'items.productVariant.attributeValues.attribute', 'coupon'])->first();
        if (! $cart) {
            $cart = $user->cart()->create();
            $cart->load(['items.product', 'items.productVariant.attributeValues.attribute', 'coupon']);
        }

        return $cart;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCartPayload(Request $request, \App\Models\Cart $cart): array
    {
        $flashByVariant = CartPricingService::activeFlashItemsByVariantId();
        $subtotal = (int) round(CartPricingService::cartSubtotal($cart, $flashByVariant));
        $couponDiscount = 0;
        $couponError = null;
        if ($cart->coupon_id && $cart->coupon) {
            $couponResult = app(CouponService::class)->validateAndComputeDiscount($cart, $cart->coupon);
            if ($couponResult['ok']) {
                $couponDiscount = $couponResult['discount'];
            } else {
                $couponError = $couponResult['message'];
                $cart->update(['coupon_id' => null]);
                $cart->refresh()->load('coupon');
            }
        }

        $items = $cart->items->map(function ($item) use ($flashByVariant, $request) {
            $unit = CartPricingService::unitPriceForItem($item, $flashByVariant);
            $line = CartPricingService::lineTotal($item, $flashByVariant);

            return (new CartItemResource($item))->additional([
                'unit_price' => $unit,
                'line_total' => $line,
            ])->resolve($request);
        })->values()->all();

        return [
            'items' => $items,
            'coupon' => $cart->coupon ? [
                'code' => $cart->coupon->code,
                'name' => $cart->coupon->name ?? $cart->coupon->code,
            ] : null,
            'subtotal' => $subtotal,
            'discount' => $couponDiscount,
            'total' => max(0, $subtotal - $couponDiscount),
            'coupon_error' => $couponError,
        ];
    }
}
