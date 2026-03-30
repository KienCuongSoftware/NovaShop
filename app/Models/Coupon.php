<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const SEGMENT_ALL = 'all';
    public const SEGMENT_VIP = 'vip';

    protected $fillable = [
        'code', 'name', 'discount_type', 'discount_value', 'min_order_amount',
        'category_id', 'starts_at', 'ends_at', 'max_uses', 'uses_count', 'is_active',
        'user_segment', 'first_order_only', 'birthday_only', 'birthday_window_days', 'min_completed_orders',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_order_amount' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'is_active' => 'boolean',
        'first_order_only' => 'boolean',
        'birthday_only' => 'boolean',
        'birthday_window_days' => 'integer',
        'min_completed_orders' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isCurrentlyValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
