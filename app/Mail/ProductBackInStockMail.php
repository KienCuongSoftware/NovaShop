<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductBackInStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
        public ?ProductVariant $variant,
        public ?User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[NovaShop] Sản phẩm đã có hàng lại: '.$this->product->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.product-back-in-stock',
        );
    }
}
