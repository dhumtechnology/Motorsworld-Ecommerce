<?php

namespace App\Actions\Admin\Appointments;

use App\Models\Appointments\Appointment;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Appointment $appointment, array $attributes): Appointment
    {
        return DB::transaction(function () use ($appointment, $attributes) {
            $appointment->update($attributes);

            return $appointment->fresh([
                'user.customerProfile',
                'serviceType',
                'vehicleModel.brand',
                'services',
            ]);
        });
    }
}
