<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['user_id', 'status', 'total_amount', 'shipping_address', 'phone', 'notes'];

    protected $casts = [
        'total_amount' => 'decimal:0',
    ];

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_AWAITING_DELIVERY = 'awaiting_delivery';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURN_REFUND = 'return_refund';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT => 'Chờ thanh toán',
            self::STATUS_SHIPPING => 'Vận chuyển',
            self::STATUS_AWAITING_DELIVERY => 'Chờ giao hàng',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_RETURN_REFUND => 'Trả hàng/Hoàn tiền',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->price * $i->quantity);
    }
}
