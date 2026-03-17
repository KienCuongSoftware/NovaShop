<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Chuẩn hóa tên sản phẩm trước khi lưu (mutator).
     * - Bỏ khoảng trắng thừa, gộp nhiều khoảng trắng thành một.
     * - Loại bỏ ký tự spam (!!!, ???...).
     * - Viết hoa chữ cái đầu mỗi từ (Str::title), tránh HOA toàn bộ.
     * Slug SEO được tạo tự động từ tên đã chuẩn hóa trong booted().
     */
    protected function setNameAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['name'] = null;
            return;
        }
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = preg_replace('/[!?]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));
        $this->attributes['name'] = Str::limit(Str::title($value), 255);
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

    /** Thuộc tính mà sản phẩm này dùng (vd: Màu, Size). Sync từ variants khi lưu. */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes', 'product_id', 'attribute_id')->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->whereNull('product_variant_id')->orderBy('sort');
    }

    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Các thuộc tính và giá trị dùng cho sản phẩm (từ variants).
     * Trả về [ "Color" => ["Đen","Trắng"], "Size" => ["Nhỏ","Lớn"] ].
     */
    public function getAttributeOptionsForFrontend(): array
    {
        $options = [];
        foreach ($this->variants as $v) {
            foreach ($v->attributeValues as $av) {
                $name = $av->attribute->name;
                if (!isset($options[$name])) {
                    $options[$name] = [];
                }
                if (!in_array($av->value, $options[$name], true)) {
                    $options[$name][] = $av->value;
                }
            }
        }
        foreach ($options as $k => $v) {
            sort($options[$k]);
        }
        return $options;
    }

    /** Tìm variant theo map attribute_name => value (vd: ["Color" => "Đen", "Size" => "Nhỏ"]). */
    public function getVariantByAttributeMap(array $selection): ?ProductVariant
    {
        $selection = array_filter($selection, fn ($v) => $v !== null && $v !== '');
        if (empty($selection)) {
            return null;
        }
        foreach ($this->variants as $variant) {
            $map = $variant->attribute_map;
            if (count($map) !== count($selection)) {
                continue;
            }
            $match = true;
            foreach ($selection as $attrName => $value) {
                if (($map[$attrName] ?? null) !== $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $variant;
            }
        }
        return null;
    }

    public function getAvailableQuantityAttribute(): int
    {
        if ($this->hasVariants()) {
            return (int) $this->variants()->sum('stock');
        }
        return (int) $this->quantity;
    }
}
