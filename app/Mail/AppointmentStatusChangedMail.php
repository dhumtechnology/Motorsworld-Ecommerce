<?php

namespace App\Mail;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing([
            'brand:id,name',
            'vehicleModel:id,name,brand_id',
            'vehicleModel.brand:id,name',
            'serviceType:id,name',
            'servicePackage:id,name',
            'user.customerProfile',
        ]);
    }

    public function envelope(): Envelope
    {
        $status = $this->appointment->status;
        $label = $status instanceof AppointmentStatus
            ? $status->label()
            : 'actualizado';

        return new Envelope(
            subject: "Tu reserva fue {$label} — ".self::BRAND_NAME,
        );
    }

    public function content(): Content
    {
        $appointment = $this->appointment;
        $status = $appointment->status instanceof AppointmentStatus
            ? $appointment->status
            : AppointmentStatus::from((string) $appointment->status);

        $name = $appointment->displayCustomerName();
        $firstName = trim(explode(' ', $name)[0] ?? '');

        if ($firstName === '' || str_starts_with(mb_strtolower($firstName), 'sin')) {
            $firstName = 'Motero';
        }

        $brandName = $appointment->brand?->name
            ?? $appointment->vehicleModel?->brand?->name
            ?? '';

        return new Content(
            view: 'emails.appointment-status-changed',
            with: [
                'firstName' => $firstName,
                'statusLabel' => $status->label(),
                'statusMessage' => $this->statusMessage($status),
                'appointmentDate' => $appointment->appointment_at?->format('d/m/Y') ?? '—',
                'appointmentTime' => $appointment->appointment_at?->format('H:i') ?? '—',
                'serviceType' => $appointment->serviceType?->name ?? '—',
                'servicePackage' => $appointment->servicePackage?->name ?? '—',
                'vehicle' => trim($brandName.' '.($appointment->vehicleModel?->name ?? '')) ?: '—',
                'plate' => $appointment->plate ?: '—',
                'cancellationReason' => $status === AppointmentStatus::Cancelled
                    ? ($appointment->cancellation_reason ?: null)
                    : null,
                'shopUrl' => route('shop.home'),
                'accountUrl' => route('shop.account.show'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }

    private function statusMessage(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::Accepted => 'Tu reserva fue aceptada. Te esperamos en la fecha y hora indicadas.',
            AppointmentStatus::Attended => 'Confirmamos que tu cita fue atendida. Gracias por confiar en Motoworld.',
            AppointmentStatus::Absent => 'Registramos tu inasistencia a la cita programada. Si necesitas reagendar, contáctanos.',
            AppointmentStatus::Cancelled => 'Tu reserva fue cancelada. Si tienes dudas o deseas una nueva cita, escríbenos.',
            default => 'El estado de tu reserva fue actualizado.',
        };
    }
}
