<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountBlockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $userEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thong bao tai khoan NovaShop da bi tam khoa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-blocked',
        );
    }
}
