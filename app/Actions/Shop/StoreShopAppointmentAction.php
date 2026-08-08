<?php

namespace App\Actions\Shop;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use App\Models\Appointments\ServicePackage;
use App\Models\Auth\User;
use App\Models\Products\VehicleModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreShopAppointmentAction
{
    public function __construct(
        private readonly GetAvailableAppointmentSlotsAction $availableSlots,
        private readonly ResolveOrCreateCustomerAction $resolveOrCreateCustomer,
    ) {}

    /**
     * @param  array{
     *     brand_id: int,
     *     vehicle_model_id: int,
     *     plate: string,
     *     km: int|float|string|null,
     *     first_name: string,
     *     last_name: string,
     *     customer_name: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     service_type_id: int,
     *     service_package_id: int,
     *     customer_email: string,
     *     appointment_date: string,
     *     appointment_time: string,
     *     comments?: string|null
     * }  $data
     */
    public function execute(array $data, ?User $user = null): Appointment
    {
        $appointmentAt = Carbon::parse($data['appointment_date'].' '.$data['appointment_time']);

        $available = $this->availableSlots->execute($appointmentAt->toDateString());
        $time = $appointmentAt->format('H:i');

        if (! in_array($time, $available, true)) {
            throw ValidationException::withMessages([
                'appointment_time' => 'El horario seleccionado ya no está disponible. Elige otro.',
            ]);
        }

        $model = VehicleModel::query()->findOrFail((int) $data['vehicle_model_id']);

        if ((int) $model->brand_id !== (int) $data['brand_id']) {
            throw ValidationException::withMessages([
                'vehicle_model_id' => 'El modelo no pertenece a la marca seleccionada.',
            ]);
        }

        $package = ServicePackage::query()
            ->where('is_active', true)
            ->findOrFail((int) $data['service_package_id']);

        if ((int) $package->service_type_id !== (int) $data['service_type_id']) {
            throw ValidationException::withMessages([
                'service_package_id' => 'El paquete no pertenece al tipo de servicio seleccionado.',
            ]);
        }

        return DB::transaction(function () use ($data, $user, $appointmentAt): Appointment {
            $customer = $this->resolveOrCreateCustomer->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'customer_document' => $data['customer_document'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
            ], $user);

            $fullName = trim($data['customer_name'] !== ''
                ? $data['customer_name']
                : trim($data['first_name'].' '.$data['last_name']));

            return Appointment::query()->create([
                'user_id' => $customer->id,
                'customer_name' => $fullName,
                'customer_document' => trim($data['customer_document']),
                'customer_phone' => trim($data['customer_phone']),
                'customer_email' => strtolower(trim($data['customer_email'])),
                'appointment_at' => $appointmentAt,
                'brand_id' => (int) $data['brand_id'],
                'vehicle_model_id' => (int) $data['vehicle_model_id'],
                'km' => $data['km'] !== null && $data['km'] !== '' ? $data['km'] : null,
                'plate' => strtoupper(trim($data['plate'])),
                'service_type_id' => (int) $data['service_type_id'],
                'service_package_id' => (int) $data['service_package_id'],
                'comments' => filled($data['comments'] ?? null) ? trim((string) $data['comments']) : null,
                'status' => AppointmentStatus::Pending,
            ]);
        });
    }
}
