<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SearchQueryTrend;
use App\Services\ProductSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function suggestions(Request $request, ProductSearchService $searchService): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $limit = max(1, min(15, (int) $request->query('limit', 8)));

        if ($q === '') {
            $trends = SearchQueryTrend::query()
                ->orderByDesc('count')
                ->orderByDesc('last_seen_at')
                ->limit($limit)
                ->get();

            $data = $trends->map(function ($t) {
                $keyword = (string) $t->keyword;

                return [
                    'name' => $keyword,
                    'price' => 0,
                    'url' => route('search', ['q' => $keyword]),
                ];
            })->values()->all();

            return response()->json(['data' => $data]);
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
