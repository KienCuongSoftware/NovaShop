<?php

namespace App\Http\Controllers;

use App\Models\ListShare;
use Illuminate\View\View;
use Illuminate\Http\Request;

class ListSharePublicController extends Controller
{
    public function showWishlist(string $token): View
    {
        $share = ListShare::query()
            ->where('token', $token)
            ->where('type', 'wishlist')
            ->with(['items.product'])
            ->firstOrFail();

        $products = $share->items
            ->pluck('product')
            ->filter()
            ->values();

        return view('share.wishlist', compact('products', 'share'));
    }

    public function showCompare(string $token): View
    {
        $share = ListShare::query()
            ->where('token', $token)
            ->where('type', 'compare')
            ->with(['items.product.brand', 'items.product.category', 'items.product.variants.attributeValues.attribute'])
            ->firstOrFail();

        $items = $share->items;
        $products = $items->pluck('product')->filter()->values();

        $attributeNames = [];
        foreach ($products as $p) {
            if (! $p->variants) {
                continue;
            }
            foreach ($p->variants as $v) {
                foreach ($v->attributeValues as $av) {
                    $attributeNames[$av->attribute->name] = true;
                }
            }
        }

        $attributeNames = array_keys($attributeNames);
        sort($attributeNames);

        return view('share.compare', compact('products', 'attributeNames', 'share'));
    }
}

