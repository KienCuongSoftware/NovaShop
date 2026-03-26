<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggestions(Request $request, ProductSearchService $searchService)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = max(1, min(15, (int) $request->query('limit', 8)));

        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $esRows = $searchService->suggest($q, $limit);
        if (is_array($esRows)) {
            $ids = array_values(array_unique(array_map(fn ($r) => (int) $r['id'], $esRows)));
            $products = Product::query()->whereIn('id', $ids)->get()->keyBy('id');

            $data = collect($esRows)->map(function ($row) use ($products) {
                $product = $products->get((int) $row['id']);
                if (! $product) {
                    return null;
                }

                return [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'slug' => (string) $product->slug,
                    'price' => (float) $product->price,
                    'url' => route('products.show', $product),
                ];
            })->filter()->values()->all();

            return response()->json(['data' => $data]);
        }

        // Fallback DB nếu ES chưa bật/lỗi
        $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
        $rows = Product::query()
            ->where('name', 'like', '%'.$esc.'%')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'price'])
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'slug' => (string) $p->slug,
                'price' => (float) $p->price,
                'url' => route('products.show', $p),
            ])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }
}

