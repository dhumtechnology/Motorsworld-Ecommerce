<?php

namespace App\Actions\Admin\Appointments;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use App\Services\Finance\DecolectaExchangeRateService;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentAction
{
    public function __construct(
        private readonly DecolectaExchangeRateService $exchangeRates,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Appointment $appointment, array $attributes): Appointment
    {
        return DB::transaction(function () use ($appointment, $attributes) {
            $previousStatus = $appointment->status;
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
                'serviceType',
                'servicePackage',
                'vehicleModel.brand',
                'services',
            ]);
        });
    }
}
