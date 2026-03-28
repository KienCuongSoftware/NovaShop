<?php

namespace App\Mail;

use App\Models\ProductReview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductReviewRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProductReview $review
    ) {}

    public function envelope(): Envelope
    {
        $productName = $this->review->product?->name ?: 'sản phẩm';

        return new Envelope(
            subject: '[NovaShop] Đánh giá của bạn chưa được duyệt: '.$productName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reviews.rejected',
        );
    }
}

