<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class AttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'value'];

    /**
     * Giá trị thuộc tính (màu sắc, size...): luôn viết hoa chữ cái đầu mỗi từ khi đọc và khi lưu.
     */
    protected function value(): CastAttribute
    {
        return CastAttribute::make(
            get: fn (?string $value) => $value !== null && $value !== '' ? Str::title($value) : $value,
            set: fn (?string $value) => $value !== null && $value !== '' ? Str::title(trim(preg_replace('/\s+/', ' ', $value))) : $value,
        );
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Attribute::class);
    }

    public function productVariants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_attribute_values', 'attribute_value_id', 'product_variant_id');
    }
}
