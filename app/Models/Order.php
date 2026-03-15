<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'status', 'total_amount', 'shipping_address', 'phone', 'notes',
        'payment_method', 'payment_status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:0',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_AWAITING_DELIVERY = 'awaiting_delivery';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURN_REFUND = 'return_refund';

    public const PAYMENT_METHOD_COD = 'cod';
    public const PAYMENT_METHOD_PAYPAL = 'paypal';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT => 'Chờ thanh toán',
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_PROCESSING => 'Đang xử lý',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->price * $i->quantity);
    }

    /** Tab "Chờ thanh toán": đơn PayPal chưa thanh toán */
    public function scopePendingPaymentTab($query)
    {
        return $query->where('payment_method', self::PAYMENT_METHOD_PAYPAL)
            ->where('payment_status', self::PAYMENT_STATUS_UNPAID);
    }

    /** Đơn PayPal chưa thanh toán → hiển thị nút "Thanh toán" */
    public function canShowPayButton(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYPAL
            && $this->payment_status === self::PAYMENT_STATUS_UNPAID;
    }

    /** Có thể hủy đơn (Chờ thanh toán, Chờ xử lý, Đang xử lý, Vận chuyển, Chờ giao hàng) */
    public function canCancel(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPING,
            self::STATUS_AWAITING_DELIVERY,
        ], true);
    }
}
