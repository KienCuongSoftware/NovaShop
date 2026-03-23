<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(50, max(1, (int) $request->query('per_page', 12)));
        $sort = $this->validatedSort($request->query('sort'));

        $query = Product::query()
            ->with([
                'category',
                'brand',
                'variants' => function ($q) {
                    $q->with(['attributeValues.attribute']);
                },
            ])
            ->withCount('variants')
            ->where('is_active', true);

        if ($request->filled('category')) {
            $slug = (string) $request->query('category');
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                $query->whereIn('category_id', $category->getDescendantIds());
            }
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->query('q'));
            if ($q !== '') {
                $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
                $query->where('name', 'like', '%'.$esc.'%');
            }
        }

        $min = $request->query('price_min');
        $max = $request->query('price_max');
        if ($min !== null && $min !== '' && (float) $min > 0) {
            $query->where('price', '>=', (float) $min);
        }
        if ($max !== null && $max !== '' && (float) $max > 0) {
            $query->where('price', '<=', (float) $max);
        }

        $query = $this->applySort($query, $sort);

        return ProductResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function show(Product $product): JsonResource
    {
        abort_unless($product->is_active, 404);
        $product->load(['category', 'brand', 'variants' => function ($q) {
            $q->with(['attributeValues.attribute']);
        }]);

        return new ProductResource($product);
    }

    protected function validatedSort(mixed $sort): string
    {
        $sort = is_string($sort) ? trim($sort) : '';
        $allowed = ['popular', 'newest', 'bestselling', 'price_asc', 'price_desc'];

        return in_array($sort, $allowed, true) ? $sort : 'popular';
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    protected function applySort($query, string $sort)
    {
        return match ($sort) {
            'newest' => $query->latest(),
            'bestselling' => $query->orderByDesc('quantity'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->inRandomOrder(),
        };
    }
}
