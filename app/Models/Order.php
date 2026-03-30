<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'coupon_id', 'discount_amount', 'address_id', 'status', 'total_amount', 'shipping_fee', 'shipping_distance_km',
        'shipping_address_snapshot', 'phone_snapshot', 'lat', 'lng', 'notes',
        'payment_method', 'payment_status', 'shipping_status',
        'stock_reserved_expires_at', 'stock_reserved_released_at',
    ];

    /** Bản chụp địa chỉ/SĐT lúc đặt hàng; accessor để view vẫn dùng $order->shipping_address / $order->phone. */
    public function getShippingAddressAttribute(): ?string
    {
        return $this->attributes['shipping_address_snapshot'] ?? null;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['phone_snapshot'] ?? null;
    }

    protected $casts = [
        'total_amount' => 'decimal:0',
        'discount_amount' => 'integer',
        'shipping_fee' => 'integer',
        'shipping_distance_km' => 'decimal:2',
        'stock_reserved_expires_at' => 'datetime',
        'stock_reserved_released_at' => 'datetime',
    ];

    /** Chờ thanh toán (đơn đã tạo, chưa thanh toán PayPal). */
    public const STATUS_UNPAID = 'unpaid';
    /** Thanh toán PayPal thất bại → vẫn hiển thị trong "Chờ thanh toán" để retry. */
    public const STATUS_PAYMENT_FAILED = 'payment_failed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_AWAITING_DELIVERY = 'awaiting_delivery';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURN_REFUND = 'return_refund';
    /** Giữ để tương thích filter cũ (map sang unpaid). */
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const PAYMENT_METHOD_COD = 'cod';
    public const PAYMENT_METHOD_PAYPAL = 'paypal';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';

    public const SHIPPING_STATUS_PENDING = 'pending';
    public const SHIPPING_STATUS_SHIPPING = 'shipping';
    public const SHIPPING_STATUS_DELIVERED = 'delivered';
    public const SHIPPING_STATUS_CANCELLED = 'cancelled';
    public const SHIPPING_STATUS_RETURNED = 'returned';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_UNPAID => 'Chờ thanh toán',
            self::STATUS_PAYMENT_FAILED => 'Chờ thanh toán',
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_SHIPPING => 'Vận chuyển',
            self::STATUS_AWAITING_DELIVERY => 'Chờ giao hàng',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_RETURN_REFUND => 'Trả hàng/Hoàn tiền',
        ];
    }

    /** Các key dùng cho tab filter (không lặp payment_failed để tránh 2 tab cùng tên). */
    public static function tabStatusKeys(): array
    {
        return [
            self::STATUS_UNPAID,
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPING,
            self::STATUS_AWAITING_DELIVERY,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_RETURN_REFUND,
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusLabels()[$status] ?? $status;
    }

    public static function shippingStatusLabels(): array
    {
        return [
            self::SHIPPING_STATUS_PENDING => 'Chưa giao',
            self::SHIPPING_STATUS_SHIPPING => 'Đang giao',
            self::SHIPPING_STATUS_DELIVERED => 'Đã giao',
            self::SHIPPING_STATUS_CANCELLED => 'Đã hủy giao',
            self::SHIPPING_STATUS_RETURNED => 'Hoàn trả',
        ];
    }

    public static function shippingStatusLabel(string $shippingStatus): string
    {
        return self::shippingStatusLabels()[$shippingStatus] ?? $shippingStatus;
    }

    /** Các key dùng cho tab filter vận chuyển. */
    public static function tabShippingStatusKeys(): array
    {
        return [
            self::SHIPPING_STATUS_PENDING,
            self::SHIPPING_STATUS_SHIPPING,
            self::SHIPPING_STATUS_DELIVERED,
            self::SHIPPING_STATUS_CANCELLED,
            self::SHIPPING_STATUS_RETURNED,
        ];
    }

    public static function mapShippingStatusFromOrderStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_SHIPPING => self::SHIPPING_STATUS_SHIPPING,
            self::STATUS_AWAITING_DELIVERY, self::STATUS_COMPLETED => self::SHIPPING_STATUS_DELIVERED,
            self::STATUS_CANCELLED => self::SHIPPING_STATUS_CANCELLED,
            self::STATUS_RETURN_REFUND => self::SHIPPING_STATUS_RETURNED,
            default => self::SHIPPING_STATUS_PENDING,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->price * $i->quantity);
    }

    /** Tab "Chờ thanh toán": đơn PayPal chưa thanh toán hoặc thanh toán thất bại (để retry). */
    public function scopePendingPaymentTab($query)
    {
        return $query->where('payment_method', self::PAYMENT_METHOD_PAYPAL)
            ->whereIn('status', [self::STATUS_UNPAID, self::STATUS_PAYMENT_FAILED]);
    }

    /** Đơn PayPal chưa thanh toán / thất bại → hiển thị nút "Thanh toán" */
    public function canShowPayButton(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYPAL
            && in_array($this->status, [self::STATUS_UNPAID, self::STATUS_PAYMENT_FAILED], true)
            && $this->payment_status !== self::PAYMENT_STATUS_PAID;
    }

    /** Có thể hủy đơn (Chờ thanh toán, Chờ xử lý, Đang xử lý, Vận chuyển, Chờ giao hàng) */
    public function canCancel(): bool
    {
        return in_array($this->status, [
            self::STATUS_UNPAID,
            self::STATUS_PAYMENT_FAILED,
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPING,
            self::STATUS_AWAITING_DELIVERY,
        ], true);
    }

    /**
     * Đơn đã hoàn thành và coi như đã giao xong → khách được phép đánh giá sản phẩm trong đơn.
     */
    public function allowsProductReviews(): bool
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return false;
        }

        return $this->shipping_status === self::SHIPPING_STATUS_DELIVERED
            || $this->shipping_status === null;
    }

    /** Đơn đã giao xong → khách có thể gửi yêu cầu trả hàng / hoàn tiền (một lần). */
    public function canRequestReturn(): bool
    {
        return $this->allowsProductReviews();
    }

    /** User đã mua product (có trong đơn completed + đã giao) → được gửi/ sửa đánh giá. */
    public static function userHasDeliveredPurchase(int $userId, int $productId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->where('shipping_status', self::SHIPPING_STATUS_DELIVERED)
                    ->orWhereNull('shipping_status');
            })
            ->whereHas('items', fn ($iq) => $iq->where('product_id', $productId))
            ->exists();
    }

    /**
     * Số ngày (min, max) từ mốc gốc đến dự kiến nhận, theo km (cùng công thức đơn hàng).
     *
     * @return array{0: int, 1: int}
     */
    public static function deliveryOffsetDaysForKm(float $km): array
    {
        $km = max(0.0, $km);
        $pMin = max(0, (int) config('delivery.processing_days_min', 1));
        $pMax = max($pMin, (int) config('delivery.processing_days_max', 2));
        $kmPerDay = max(1.0, (float) config('delivery.km_per_day', 45));
        $maxTransit = max(1, (int) config('delivery.max_transit_days', 8));
        $buffer = max(0, (int) config('delivery.buffer_days', 1));

        $transit = max(1, (int) ceil($km / $kmPerDay));
        $transit = min($transit, $maxTransit);

        $minTotal = $pMin + $transit;
        $maxTotal = $pMax + $transit + $buffer;

        return [$minTotal, $maxTotal];
    }

    /** @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon} */
    public static function estimatedDeliveryDateRangeFromDistanceKm(float $km, $baseDate = null): array
    {
        $base = ($baseDate ?? now())->copy()->startOfDay();
        [$minDays, $maxDays] = static::deliveryOffsetDaysForKm($km);

        return [
            $base->copy()->addDays($minDays),
            $base->copy()->addDays($maxDays),
        ];
    }

    /**
     * Khoảng ngày (tối thiểu, tối đa) từ ngày đặt đến dự kiến nhận, dựa trên khoảng cách giao hàng (km).
     *
     * @return array{0: int, 1: int} số ngày lịch (không phải chỉ ngày làm việc)
     */
    public function estimatedDeliveryOffsetDays(): array
    {
        $km = max(0.0, (float) ($this->shipping_distance_km ?? 0));

        return static::deliveryOffsetDaysForKm($km);
    }

    /** @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}|null */
    public function estimatedDeliveryDateRange(): ?array
    {
        if (! $this->created_at) {
            return null;
        }
        [$minDays, $maxDays] = $this->estimatedDeliveryOffsetDays();
        $base = $this->created_at->copy()->startOfDay();

        return [
            $base->copy()->addDays($minDays),
            $base->copy()->addDays($maxDays),
        ];
    }

    public function estimatedDeliveryDateLabel(): string
    {
        $range = $this->estimatedDeliveryDateRange();
        if ($range === null) {
            return '—';
        }
        /** @var Carbon $from */
        /** @var Carbon $to */
        [$from, $to] = $range;
        $km = $this->shipping_distance_km;

        if ($km !== null) {
            return sprintf(
                '%s — %s (ước tính theo ~%s km)',
                $from->format('d/m/Y'),
                $to->format('d/m/Y'),
                number_format((float) $km, 1, ',', '.')
            );
        }

        return $from->format('d/m/Y').' — '.$to->format('d/m/Y');
    }
}
