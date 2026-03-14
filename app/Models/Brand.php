<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'logo'];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand) {
            if (empty($brand->slug) || $brand->isDirty('name')) {
                $base = Str::slug($brand->name ?: 'brand');
                $slug = $base;
                $n = 0;
                while (true) {
                    $q = static::query()->where('slug', $slug);
                    if ($brand->exists) {
                        $q->where('id', '!=', $brand->id);
                    }
                    if (!$q->exists()) {
                        break;
                    }
                    $slug = $base . '-' . (++$n);
                }
                $brand->slug = $slug;
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
