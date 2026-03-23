<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CartItem */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => (int) $this->quantity,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'image' => $this->product->image,
            ]),
            'variant' => $this->whenLoaded('productVariant', fn () => $this->productVariant ? [
                'id' => $this->productVariant->id,
                'display_name' => $this->productVariant->display_name,
                'price' => (float) $this->productVariant->price,
                'stock' => (int) $this->productVariant->stock,
            ] : null),
            'unit_price' => $this->additional['unit_price'] ?? null,
            'line_total' => $this->additional['line_total'] ?? null,
        ];
    }
}
