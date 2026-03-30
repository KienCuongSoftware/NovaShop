<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $previousStatus,
        public string $currentStatus,
    ) {}

    public function envelope(): Envelope
    {
        $label = Order::statusLabel($this->currentStatus);

        return new Envelope(
            subject: '[NovaShop] Đơn hàng #'.$this->order->id.' — '.$label,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status-changed',
        );
    }
}
