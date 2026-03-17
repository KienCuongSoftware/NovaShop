<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'price', 'stock', 'sku'];

    protected $casts = [
        'price' => 'decimal:0',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
