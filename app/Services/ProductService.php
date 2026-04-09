<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function syncProductAttributes(Product $product): void
    {
        $attributeIds = $product->variants()->with('attributeValues')->get()
            ->flatMap(fn ($v) => $v->attributeValues->pluck('attribute_id'))
            ->unique()
            ->values()
            ->all();
        $product->attributes()->sync($attributeIds);
    }

    public function propagateImageByColor(Product $product): void
    {
        $colorAttrId = $this->getColorAttributeId($product);
        if ($colorAttrId === null) {
            return;
        }
        $variants = $product->variants()->with(['attributeValues', 'images'])->get();
        $byColor = [];
        foreach ($variants as $v) {
            $colorValueId = $v->attributeValues->firstWhere('attribute_id', $colorAttrId)?->id;
            if ($colorValueId === null) {
                $colorValueId = 'other';
            }
            $byColor[$colorValueId] = $byColor[$colorValueId] ?? [];
            $byColor[$colorValueId][] = $v;
        }
        foreach ($byColor as $variantsInColor) {
            $sourceVariant = null;
            foreach ($variantsInColor as $v) {
                if ($v->images->isNotEmpty()) {
                    $sourceVariant = $v;
                    break;
                }
            }
            if ($sourceVariant === null) {
                continue;
            }
            $path = $sourceVariant->images->first()->image;
            foreach ($variantsInColor as $v) {
                if ($v->id === $sourceVariant->id) {
                    continue;
                }
                if ($v->images->isEmpty()) {
                    $v->images()->create(['product_id' => $product->id, 'image' => $path, 'sort' => 0]);
                }
            }
        }
    }

    public function deleteVariantImages(ProductVariant $variant): void
    {
        $productId = $variant->product_id;
        foreach ($variant->images as $img) {
            $path = $img->image;
            $img->delete();
            if ($path && ProductImage::where('product_id', $productId)->where('image', $path)->count() === 0) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }

    private function getColorAttributeId(Product $product): ?int
    {
        $attributes = $product->attributes()->get();
        foreach ($attributes as $attr) {
            $name = strtolower($attr->name ?? '');
            if (in_array($name, ['màu', 'color', 'mau'], true) || str_contains($name, 'màu') || str_contains($name, 'color')) {
                return (int) $attr->id;
            }
        }
        return $attributes->isNotEmpty() ? (int) $attributes->first()->id : null;
    }
}
