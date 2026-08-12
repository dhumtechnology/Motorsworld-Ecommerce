<?php

namespace App\Mail;

use App\Models\Appointments\Appointment;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing([
            'brand:id,name',
            'vehicleModel:id,name',
            'serviceType:id,name',
            'servicePackage:id,name,price,currency',
        ]);
    }

    public function envelope(): Envelope
    {
        $when = $this->appointment->appointment_at?->format('d/m/Y H:i') ?? '';

        return new Envelope(
            subject: 'Confirmación de reserva'.($when !== '' ? " — {$when}" : '').' — '.self::BRAND_NAME,
        );
    }

    public function content(): Content
    {
        $appointment = $this->appointment;
        $firstName = trim(explode(' ', $appointment->displayCustomerName())[0] ?? '');

        if ($firstName === '' || $firstName === 'Sin') {
            $firstName = 'Motero';
        }

        $package = $appointment->servicePackage;
        $packagePrice = $package !== null && $package->price !== null
            ? Currency::format((float) $package->price, $package->currency)
            : null;

        return new Content(
            view: 'emails.appointment-confirmation',
            with: [
                'firstName' => $firstName,
                'customerName' => $appointment->displayCustomerName(),
                'customerEmail' => $appointment->displayCustomerEmail(),
                'customerDocument' => $appointment->displayCustomerDocument(),
                'customerPhone' => $appointment->displayCustomerPhone(),
                'appointmentDate' => $appointment->appointment_at?->format('d/m/Y') ?? '—',
                'appointmentTime' => $appointment->appointment_at?->format('H:i') ?? '—',
                'serviceType' => $appointment->serviceType?->name ?? '—',
                'servicePackage' => $package?->name ?? '—',
                'packagePrice' => $packagePrice,
                'vehicle' => trim(($appointment->brand?->name ?? '').' '.($appointment->vehicleModel?->name ?? '')) ?: '—',
                'plate' => $appointment->plate ?: '—',
                'km' => $appointment->km !== null ? (string) $appointment->km : null,
                'comments' => $appointment->comments,
                'shopUrl' => route('shop.home'),
                'servicesUrl' => route('shop.services.index'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }
}
