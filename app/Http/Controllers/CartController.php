<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->with(['items.product'])->first();
        if (!$cart) {
            $cart = $user->cart()->create();
            $cart->load(['items.product']);
        }
        return view('user.cart.index', compact('cart'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);
        $productId = (int) $request->input('product_id');
        $quantity = max(1, (int) ($request->input('quantity') ?? 1));

        $product = Product::findOrFail($productId);
        if ($quantity > $product->quantity) {
            return back()->with('error', 'Số lượng vượt quá tồn kho.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart()->firstOrCreate([]);
        $item = $cart->items()->where('product_id', $productId)->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;
            if ($newQty > $product->quantity) {
                return back()->with('error', 'Số lượng vượt quá tồn kho.');
            }
            $item->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create(['product_id' => $productId, 'quantity' => $quantity]);
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
        $item = $cart->items()->findOrFail($request->input('cart_item_id'));
        $quantity = (int) $request->input('quantity');
        if ($quantity > $item->product->quantity) {
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
