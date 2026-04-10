<?php

namespace App\Observers;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    /**
     * Trạng thái trước khi save, theo id đơn — dùng static thay vì property instance
     * vì container có thể resolve observer khác nhau giữa updating / updated.
     *
     * @var array<int, string|null>
     */
    protected static array $previousStatusByOrderId = [];

    public function updating(Order $order): void
    {
        if ($order->exists && $order->isDirty('status')) {
            self::$previousStatusByOrderId[(int) $order->getKey()] = $order->getOriginal('status');
        }
    }

    public function updated(Order $order): void
    {
        $id = (int) $order->getKey();
        if (! array_key_exists($id, self::$previousStatusByOrderId)) {
            return;
        }

        $previous = self::$previousStatusByOrderId[$id];
        unset(self::$previousStatusByOrderId[$id]);

        if ($previous === (string) $order->status) {
            return;
        }

        self::notifyStatusChange($order, $previous === null ? null : (string) $previous, (string) $order->status);
    }

    /**
     * Gửi email cập nhật trạng thái / đơn mới. Luôn load đủ quan hệ để template có tổng tiền & dòng hàng.
     * (Không gửi trong created: lúc đó total_amount chưa được OrderPlacementService cập nhật.)
     */
    public static function notifyStatusChange(Order $order, ?string $previousStatus, string $currentStatus): void
    {
        $order->loadMissing([
            'user',
            'coupon',
            'items.product',
            'items.productVariant.attributeValues.attribute',
        ]);
        $user = $order->user;
        $email = $user?->email;
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($email)->send(new OrderStatusChangedMail($order, $previousStatus, $currentStatus));
        } catch (\Throwable $e) {
            Log::warning('Order status email failed', [
                'order_id' => $order->id,
                'to' => $email,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
