<?php

namespace App\Actions\Admin\Appointments;

use App\Enums\Appointments\AppointmentStatus;
use App\Mail\AppointmentStatusChangedMail;
use App\Models\Appointments\Appointment;
use App\Services\Finance\DecolectaExchangeRateService;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UpdateAppointmentAction
{
    /** @var list<AppointmentStatus> */
    private const NOTIFY_STATUSES = [
        AppointmentStatus::Accepted,
        AppointmentStatus::Attended,
        AppointmentStatus::Absent,
        AppointmentStatus::Cancelled,
    ];

    public function __construct(
        private readonly DecolectaExchangeRateService $exchangeRates,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Appointment $appointment, array $attributes): Appointment
    {
        $previousStatus = $appointment->status instanceof AppointmentStatus
            ? $appointment->status
            : AppointmentStatus::from((string) $appointment->status);

        $appointment = DB::transaction(function () use ($appointment, $attributes, $previousStatus) {
            $nextStatus = isset($attributes['status'])
                ? AppointmentStatus::from((string) $attributes['status'])
                : $previousStatus;

            if (
                $nextStatus === AppointmentStatus::Attended
                && $previousStatus !== AppointmentStatus::Attended
            ) {
                $appointment->loadMissing(['servicePackage', 'services']);

                $amount = $appointment->revenueAmount();
                $currency = $appointment->revenueCurrency();
                $snapshot = $this->exchangeRates->snapshotForOrder();

                $attributes['charged_amount'] = $amount;
                $attributes['charged_currency'] = Currency::normalize($currency);
                $attributes['exchange_rate_buy'] = $snapshot['buy'] ?? null;
                $attributes['exchange_rate_sell'] = $snapshot['sell'] ?? null;
                $attributes['exchange_rate_date'] = $snapshot['date'] ?? null;
                $attributes['attended_at'] = now();
            }

            if (
                $nextStatus !== AppointmentStatus::Attended
                && $previousStatus === AppointmentStatus::Attended
            ) {
                $attributes['charged_amount'] = null;
                $attributes['charged_currency'] = null;
                $attributes['exchange_rate_buy'] = null;
                $attributes['exchange_rate_sell'] = null;
                $attributes['exchange_rate_date'] = null;
                $attributes['attended_at'] = null;
            }

            $appointment->update($attributes);

            return $appointment->fresh([
                'user.customerProfile',
                'brand',
                'serviceType',
                'servicePackage',
                'vehicleModel.brand',
                'services',
            ]);
        });

        $nextStatus = $appointment->status instanceof AppointmentStatus
            ? $appointment->status
            : AppointmentStatus::from((string) $appointment->status);

        if ($previousStatus !== $nextStatus && in_array($nextStatus, self::NOTIFY_STATUSES, true)) {
            $this->notifyCustomer($appointment);
        }

        return $appointment;
    }

    private function notifyCustomer(Appointment $appointment): void
    {
        $email = $appointment->displayCustomerEmail();

        if ($email === '' || $email === '—') {
            return;
        }

        try {
            Mail::to($email)->send(new AppointmentStatusChangedMail($appointment));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
