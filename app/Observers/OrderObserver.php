<?php

namespace App\Observers;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    protected ?string $pendingOldStatus = null;

    protected ?int $pendingOrderId = null;

    public function created(Order $order): void
    {
        $this->sendStatusMail($order, null, (string) $order->status);
    }

    public function updating(Order $order): void
    {
        if ($order->exists && $order->isDirty('status')) {
            $this->pendingOldStatus = $order->getOriginal('status');
            $this->pendingOrderId = $order->id;
        }
    }

    public function updated(Order $order): void
    {
        if ($this->pendingOrderId !== $order->id || $this->pendingOldStatus === null) {
            return;
        }

        $previous = $this->pendingOldStatus;
        $this->pendingOldStatus = null;
        $this->pendingOrderId = null;

        if ($previous === (string) $order->status) {
            return;
        }

        $this->sendStatusMail($order, $previous, (string) $order->status);
    }

    protected function sendStatusMail(Order $order, ?string $previousStatus, string $currentStatus): void
    {
        $order->loadMissing('user');
        $user = $order->user;
        $email = $user?->email;
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            // Gửi đồng bộ: tránh mail kẹt trong queue khi chưa chạy `queue:work`.
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
