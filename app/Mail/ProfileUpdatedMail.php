<?php

namespace App\Mail;

use App\Models\Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Actualizaste tus datos en '.self::BRAND_NAME,
        );
    }

    public function content(): Content
    {
        $profile = $this->user->customerProfile;
        $firstName = $profile?->first_name ?? 'Motero';

        return new Content(
            view: 'emails.profile-updated',
            with: [
                'firstName' => $firstName,
                'email' => $this->user->email,
                'accountUrl' => route('shop.account.show'),
                'shopUrl' => route('shop.home'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
                'updatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ],
        );
    }
}
