<?php

namespace App\Http\Controllers;

use App\Models\CompareItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompareController extends Controller
{
    public const MAX_ITEMS = 4;

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $items = $user
            ->compareItems()
            ->with([
                'product.brand',
                'product.category',
                'product.variants.attributeValues.attribute',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $products = $items->pluck('product')->filter();

        $attributeNames = [];
        foreach ($products as $p) {
            foreach ($p->variants as $v) {
                foreach ($v->attributeValues as $av) {
                    $attributeNames[$av->attribute->name] = true;
                }
            }
        }
        $attributeNames = array_keys($attributeNames);
        sort($attributeNames);

        return view('user.compare.index', compact('items', 'products', 'attributeNames'));
    }

    public function add(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        /** @var User $user */
        $user = Auth::user();
        $productId = (int) $request->input('product_id');

        if (CompareItem::where('user_id', $user->id)->where('product_id', $productId)->exists()) {
            return back()->with('info', 'Sản phẩm đã có trong danh sách so sánh.');
        }

        $count = CompareItem::where('user_id', $user->id)->count();
        if ($count >= self::MAX_ITEMS) {
            return back()->with('error', 'So sánh tối đa '.self::MAX_ITEMS.' sản phẩm.');
        }

        CompareItem::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'sort_order' => $count,
        ]);

        return back()->with('success', 'Đã thêm vào so sánh.');
    }

    public function remove(Product $product)
    {
        CompareItem::where('user_id', Auth::id())->where('product_id', $product->id)->delete();

        return back()->with('success', 'Đã xóa khỏi so sánh.');
    }

    public function clear()
    {
        CompareItem::where('user_id', Auth::id())->delete();

        return redirect()->route('compare.index')->with('success', 'Đã xóa danh sách so sánh.');
    }
}
