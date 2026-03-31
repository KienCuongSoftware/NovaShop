<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationService
{
    public const VARIANT_V1 = 'v1';
    public const VARIANT_V2 = 'v2';

    /**
     * @return Collection<int, Product>
     */
    public function suggestV2(?User $user, array $recentProductIds, array $recentCategoryIds, int $max = 20): Collection
    {
        $max = max(4, min(40, $max));
        $recentProductIds = array_values(array_filter(array_map('intval', $recentProductIds)));
        $recentCategoryIds = array_values(array_filter(array_map('intval', $recentCategoryIds)));

        $signal = $this->buildSignal($user, $recentProductIds, $recentCategoryIds);
        $candidatePool = $this->buildCandidates($signal, $recentProductIds, max($max * 3, 60));

        if ($candidatePool->isEmpty()) {
            return collect();
        }

        $scored = $candidatePool
            ->map(function (Product $product) use ($signal): Product {
                $score = 0.0;
                $score += (float) ($signal['product'][$product->id] ?? 0) * 2.5;
                $score += (float) ($signal['category'][$product->category_id] ?? 0) * 1.25;
                $score += (float) ($signal['brand'][(int) $product->brand_id] ?? 0) * 1.0;
                $score += ((float) ($product->approved_reviews_avg_rating ?? 0)) * 0.3;
                $score += ((int) ($product->approved_reviews_count ?? 0)) * 0.01;
                $product->setAttribute('rec_score', round($score, 4));

                return $product;
            })
            ->sortByDesc(fn (Product $p) => [$p->rec_score, $p->id])
            ->values();

        return $scored->take($max)->values();
    }

    /**
     * @param  array{product: array<int,float>, category: array<int,float>, brand: array<int,float>}  $signal
     * @return Collection<int, Product>
     */
    protected function buildCandidates(array $signal, array $excludeProductIds, int $limit): Collection
    {
        $categoryIds = array_keys($signal['category']);
        $brandIds = array_keys($signal['brand']);

        $query = Product::query()
            ->with('category')
            ->withCount([
                'reviews as approved_reviews_count' => fn ($rq) => $rq->where('is_approved', true),
            ])
            ->withAvg([
                'reviews as approved_reviews_avg_rating' => fn ($rq) => $rq->where('is_approved', true),
            ], 'rating')
            ->where('is_active', true)
            ->when(! empty($excludeProductIds), fn ($q) => $q->whereNotIn('id', $excludeProductIds))
            ->when(! empty($categoryIds) || ! empty($brandIds), function ($q) use ($categoryIds, $brandIds) {
                $q->where(function ($x) use ($categoryIds, $brandIds) {
                    if (! empty($categoryIds)) {
                        $x->whereIn('category_id', $categoryIds);
                    }
                    if (! empty($brandIds)) {
                        $method = ! empty($categoryIds) ? 'orWhereIn' : 'whereIn';
                        $x->{$method}('brand_id', $brandIds);
                    }
                });
            })
            ->limit($limit);

        $rows = $query->get();
        if ($rows->isNotEmpty()) {
            return $rows;
        }

        // Final fallback: bestselling completed products.
        $topIds = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) AS qty')
            ->whereHas('order', fn ($oq) => $oq->where('status', Order::STATUS_COMPLETED))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit($limit)
            ->pluck('product_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($topIds)) {
            return collect();
        }

        return Product::query()
            ->with('category')
            ->withCount([
                'reviews as approved_reviews_count' => fn ($rq) => $rq->where('is_approved', true),
            ])
            ->withAvg([
                'reviews as approved_reviews_avg_rating' => fn ($rq) => $rq->where('is_approved', true),
            ], 'rating')
            ->whereIn('id', $topIds)
            ->when(! empty($excludeProductIds), fn ($q) => $q->whereNotIn('id', $excludeProductIds))
            ->get();
    }

    /**
     * @return array{product: array<int,float>, category: array<int,float>, brand: array<int,float>}
     */
    protected function buildSignal(?User $user, array $recentProductIds, array $recentCategoryIds): array
    {
        $productWeights = [];
        $categoryWeights = [];
        $brandWeights = [];

        // Recent viewed products: newest has highest weight.
        foreach ($recentProductIds as $idx => $pid) {
            $productWeights[$pid] = ($productWeights[$pid] ?? 0) + max(1, (count($recentProductIds) - $idx));
        }
        foreach ($recentCategoryIds as $idx => $cid) {
            $categoryWeights[$cid] = ($categoryWeights[$cid] ?? 0) + max(1, (count($recentCategoryIds) - $idx));
        }

        if (! $user) {
            return ['product' => $productWeights, 'category' => $categoryWeights, 'brand' => $brandWeights];
        }

        $cart = $user->cart()->with(['items.product'])->first();
        if ($cart && $cart->items->isNotEmpty()) {
            foreach ($cart->items as $item) {
                $pid = (int) $item->product_id;
                $qty = max(1, (int) $item->quantity);
                $productWeights[$pid] = ($productWeights[$pid] ?? 0) + $qty * 4;
                $cid = (int) ($item->product?->category_id ?? 0);
                $bid = (int) ($item->product?->brand_id ?? 0);
                if ($cid > 0) {
                    $categoryWeights[$cid] = ($categoryWeights[$cid] ?? 0) + $qty * 3;
                }
                if ($bid > 0) {
                    $brandWeights[$bid] = ($brandWeights[$bid] ?? 0) + $qty * 3;
                }
            }
        }

        $orderedRows = OrderItem::query()
            ->selectRaw('order_items.product_id, products.category_id, products.brand_id, SUM(order_items.quantity) as qty')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.user_id', $user->id)
            ->whereIn('orders.status', [Order::STATUS_COMPLETED, Order::STATUS_AWAITING_DELIVERY])
            ->groupBy('order_items.product_id', 'products.category_id', 'products.brand_id')
            ->orderByDesc('qty')
            ->limit(80)
            ->get();

        foreach ($orderedRows as $row) {
            $qty = max(1, (int) $row->qty);
            $pid = (int) $row->product_id;
            $cid = (int) $row->category_id;
            $bid = (int) $row->brand_id;

            $productWeights[$pid] = ($productWeights[$pid] ?? 0) + $qty * 2;
            if ($cid > 0) {
                $categoryWeights[$cid] = ($categoryWeights[$cid] ?? 0) + $qty * 2;
            }
            if ($bid > 0) {
                $brandWeights[$bid] = ($brandWeights[$bid] ?? 0) + $qty * 2;
            }
        }

        return ['product' => $productWeights, 'category' => $categoryWeights, 'brand' => $brandWeights];
    }
}
