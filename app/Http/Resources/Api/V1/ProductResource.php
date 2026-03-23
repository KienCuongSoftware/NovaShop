<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'old_price' => $this->old_price !== null ? (float) $this->old_price : null,
            'effective_price' => (float) $this->effective_price,
            'image' => $this->image,
            'quantity' => (int) $this->quantity,
            'effective_stock' => $this->effective_stock,
            'has_variants' => isset($this->resource->variants_count)
                ? ((int) $this->resource->variants_count > 0)
                : ($this->relationLoaded('variants') ? $this->variants->isNotEmpty() : false),
            'is_active' => (bool) $this->is_active,
            'url' => route('products.show', $this->resource),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
            'variants' => $this->whenLoaded('variants', function () {
                return $this->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => (float) $v->price,
                    'stock' => (int) $v->stock,
                    'display_name' => $v->display_name,
                ])->values()->all();
            }),
        ];
    }
}
