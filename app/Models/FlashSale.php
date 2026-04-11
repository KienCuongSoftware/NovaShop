<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSale extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';
    public const STATUS_SCHEDULED = 'scheduled';

    protected $fillable = ['name', 'start_time', 'end_time', 'status'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class, 'flash_sale_id');
    }

    /** Trạng thái suy ra từ thời gian (ưu tiên hiển thị admin / đồng bộ khi lưu). */
    public function derivedStatus(): string
    {
        return self::computeStatus($this->start_time, $this->end_time);
    }

    public static function computeStatus(Carbon $start, Carbon $end): string
    {
        $now = now();
        if ($now->lt($start)) {
            return self::STATUS_SCHEDULED;
        }
        if ($now->lt($end)) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_ENDED;
    }

    /** Scope: khung giờ đang diễn ra (start_time <= now < end_time). Không dùng cột status — status chỉ để admin/seed ghi nhận. */
    public function scopeActive($query)
    {
        $now = now();

        return $query->where('start_time', '<=', $now)
            ->where('end_time', '>', $now)
            ->orderBy('start_time');
    }

    /** Scope: các slot trong ngày (theo ngày start_time). */
    public function scopeTodaySlots($query)
    {
        return $query->whereDate('start_time', now()->toDateString())->orderBy('start_time');
    }

    /** Lấy slot hiện tại (đang diễn ra) hoặc slot tiếp theo (sắp tới). */
    public static function getCurrentOrNext()
    {
        $now = now();
        $current = static::query()
            ->where('start_time', '<=', $now)
            ->where('end_time', '>', $now)
            ->orderBy('start_time')
            ->with('items.productVariant.product')
            ->first();
        if ($current) {
            return $current;
        }
        return static::where('start_time', '>', $now)
            ->orderBy('start_time')
            ->with('items.productVariant.product')
            ->first();
    }

    /** Danh sách slot trong ngày (cho tab UI). */
    public static function getTodaySlots()
    {
        return static::todaySlots()->get();
    }
}
