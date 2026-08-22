<?php

namespace App\Mail;

use App\Models\Claims\ClaimBookEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimBookReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(public ClaimBookEntry $entry) {}

    public function envelope(): Envelope
    {
        $type = $this->entry->claim_type->label();

        return new Envelope(
            subject: "Respuesta a tu {$type} {$this->entry->code} — ".self::BRAND_NAME,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.claim-book-reply',
            with: [
                'firstName' => $this->entry->first_name,
                'code' => $this->entry->code,
                'claimType' => $this->entry->claim_type->label(),
                'reply' => $this->entry->admin_reply,
                'shopUrl' => route('shop.home'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }
}
