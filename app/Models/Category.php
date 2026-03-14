<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'image', 'parent_id'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (empty($category->slug) || $category->isDirty('name')) {
                $base = Str::slug($category->name ?: 'category');
                $slug = $base;
                $n = 0;
                while (true) {
                    $q = static::query()->where('slug', $slug);
                    if ($category->exists) {
                        $q->where('id', '!=', $category->id);
                    }
                    if (!$q->exists()) {
                        break;
                    }
                    $slug = $base . '-' . (++$n);
                }
                $category->slug = $slug;
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Scope: chỉ danh mục gốc. */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Scope: chỉ danh mục lá (không có con, dùng để gán sản phẩm). */
    public function scopeLeaves($query)
    {
        return $query->whereDoesntHave('children');
    }

    /** Danh mục gốc (không có parent). */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /** Danh mục lá (không có con, dùng để gán sản phẩm). */
    public function isLeaf(): bool
    {
        return !$this->children()->exists();
    }

    /** Lấy tất cả ID danh mục con đệ quy (bao gồm chính nó). */
    public function getDescendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        return $ids;
    }

    /** Breadcrumb path (vd: Thời trang > Áo nam > Áo thun). */
    public function getBreadcrumbPath(): array
    {
        $path = [];
        $cat = $this;
        while ($cat) {
            array_unshift($path, $cat);
            $cat = $cat->parent;
        }
        return $path;
    }

    /** Chuỗi đường dẫn đầy đủ (vd: Thời trang > Áo nam > Áo thun). */
    public function getFullPathAttribute(): string
    {
        return implode(' › ', array_map(fn ($c) => $c->name, $this->getBreadcrumbPath()));
    }
}
