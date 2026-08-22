<?php

namespace App\Mail;

use App\Models\Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        $name = $this->user->customerProfile?->first_name
            ?? self::BRAND_NAME;

        return new Envelope(
            subject: 'Bienvenido a '.self::BRAND_NAME.", {$name}",
        );
    }

    public function content(): Content
    {
        $profile = $this->user->customerProfile;
        $firstName = $profile?->first_name ?? 'Motero';

        return new Content(
            view: 'emails.welcome-customer',
            with: [
                'firstName' => $firstName,
                'fullName' => trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? '')),
                'email' => $this->user->email,
                'shopUrl' => route('shop.home'),
                'loginUrl' => route('login'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }
}
