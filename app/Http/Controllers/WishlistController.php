<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $items = $user->wishlistItems()->with(['product.brand', 'product.category'])->orderByDesc('id')->paginate(12);

        return view('user.wishlist.index', compact('items'));
    }

    public function toggle(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        /** @var User $user */
        $user = Auth::user();
        $productId = (int) $request->input('product_id');

        $row = WishlistItem::where('user_id', $user->id)->where('product_id', $productId)->first();
        if ($row) {
            $row->delete();

            return back()->with('success', 'Đã xóa khỏi yêu thích.');
        }

        WishlistItem::create(['user_id' => $user->id, 'product_id' => $productId]);

        return back()->with('success', 'Đã thêm vào yêu thích.');
    }

    public function remove(Product $product)
    {
        WishlistItem::where('user_id', Auth::id())->where('product_id', $product->id)->delete();

        return back()->with('success', 'Đã xóa khỏi yêu thích.');
    }
}
