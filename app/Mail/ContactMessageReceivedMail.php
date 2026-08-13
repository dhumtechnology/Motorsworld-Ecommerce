<?php

namespace App\Mail;

use App\Models\Contacts\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibimos tu mensaje — '.self::BRAND_NAME." ({$this->contactMessage->code})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message-received',
            with: [
                'firstName' => $this->contactMessage->first_name,
                'code' => $this->contactMessage->code,
                'customerMessage' => $this->contactMessage->message,
                'shopUrl' => route('shop.home'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }
}
