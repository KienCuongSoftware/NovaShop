<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'price', 'stock', 'sku'];

    protected $casts = [
        'price' => 'decimal:0',
        'stock' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (ProductVariant $variant) {
            $variant->syncProductPriceQuantity();
        });
        static::deleted(function (ProductVariant $variant) {
            $variant->syncProductPriceQuantity();
        });
    }

    /** Đồng bộ product.price = min(variants.price), product.quantity = sum(variants.stock) để hiển thị. */
    public function syncProductPriceQuantity(): void
    {
        $product = $this->product;
        if (!$product) {
            return;
        }
        $minPrice = (float) static::query()->where('product_id', $product->id)->min('price');
        $totalStock = (int) static::query()->where('product_id', $product->id)->sum('stock');
        $product->updateQuietly(['price' => $minPrice, 'quantity' => $totalStock]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values', 'product_variant_id', 'attribute_value_id')
            ->with('attribute');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id')->orderBy('sort');
    }

    public function flashSaleItems(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class, 'product_variant_id');
    }

    /** Tên hiển thị từ tổ hợp giá trị thuộc tính (vd: "Đen / Nhỏ / Túi dài"). */
    public function getDisplayNameAttribute(): string
    {
        $parts = $this->attributeValues->sortBy('attribute.name')->map(fn ($av) => $av->value)->values()->all();
        return implode(' / ', $parts) ?: '—';
    }

    /** Trả về map attribute_name => value cho frontend (vd: ["Color" => "Đen", "Size" => "Nhỏ"]). */
    public function getAttributeMapAttribute(): array
    {
        $map = [];
        foreach ($this->attributeValues as $av) {
            $map[$av->attribute->name] = $av->value;
        }
        return $map;
    }

    /** URL ảnh chính của variant (ảnh đầu tiên của variant hoặc product). */
    public function getMainImageUrlAttribute(): ?string
    {
        $img = $this->images()->first();
        if ($img) {
            return '/images/products/' . basename($img->image);
        }
        if ($this->product && $this->product->image) {
            return '/images/products/' . basename($this->product->image);
        }
        return null;
    }
}
