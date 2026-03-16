<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'price',
        'old_price',
        'image',
        'quantity',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (empty($product->slug) || $product->isDirty('name')) {
                $base = Str::slug($product->name ?: 'product');
                $slug = $base;
                $n = 0;
                while (true) {
                    $q = static::query()->where('slug', $slug);
                    if ($product->exists) {
                        $q->where('id', '!=', $product->id);
                    }
                    if (!$q->exists()) {
                        break;
                    }
                    $slug = $base . '-' . (++$n);
                }
                $product->slug = $slug;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    public function getDistinctSizesAttribute(): array
    {
        return $this->variants()
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->distinct()
            ->pluck('size')
            ->sort()
            ->values()
            ->all();
    }

    public function getDistinctColorsAttribute(): array
    {
        return $this->variants()
            ->whereNotNull('color')
            ->where('color', '!=', '')
            ->distinct()
            ->pluck('color')
            ->sort()
            ->values()
            ->all();
    }

    public function getVariantByAttributes(?string $size, ?string $color): ?ProductVariant
    {
        $q = $this->variants();
        if ($size !== null && $size !== '') {
            $q->where('size', $size);
        } else {
            $q->whereNull('size');
        }
        if ($color !== null && $color !== '') {
            $q->where('color', $color);
        } else {
            $q->whereNull('color');
        }
        return $q->first();
    }

    public function getAvailableQuantityAttribute(): int
    {
        if ($this->hasVariants()) {
            return (int) $this->variants()->sum('quantity');
        }
        return (int) $this->quantity;
    }
}
