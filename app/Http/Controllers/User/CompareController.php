<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CompareItem;
use App\Models\ListShare;
use App\Models\ListShareItem;
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

    public function share()
    {
        $user = Auth::user();

        $compareItems = CompareItem::query()
            ->where('user_id', $user->id)
            ->orderBy('sort_order')
            ->get(['product_id', 'sort_order']);

        $token = bin2hex(random_bytes(16));

        $share = ListShare::query()->create([
            'user_id' => $user->id,
            'type' => 'compare',
            'token' => $token,
        ]);

        foreach ($compareItems as $row) {
            ListShareItem::query()->create([
                'list_share_id' => $share->id,
                'product_id' => (int) $row->product_id,
                'sort_order' => (int) $row->sort_order,
            ]);
        }

        $link = route('share.compare.show', ['token' => $token]);

        return back()->with('share_link', $link);
    }
}
