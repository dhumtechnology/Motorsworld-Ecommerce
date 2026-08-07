<?php

namespace App\Actions\Shop;

use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Auth\UserStatus;
use App\Models\Appointments\Appointment;
use App\Models\Appointments\ServicePackage;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use App\Models\Products\VehicleModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreShopAppointmentAction
{
    public function __construct(
        private readonly GetAvailableAppointmentSlotsAction $availableSlots,
    ) {}

    /**
     * @param  array{
     *     brand_id: int,
     *     vehicle_model_id: int,
     *     plate: string,
     *     km: int|float|string|null,
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
            $customer = $this->resolveCustomer($data, $user);

            return Appointment::query()->create([
                'user_id' => $customer->id,
                'customer_name' => trim($data['customer_name']),
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

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     customer_email: string
     * }  $data
     */
    private function resolveCustomer(array $data, ?User $authenticated): User
    {
        if ($authenticated !== null) {
            $this->ensureCustomerProfile($authenticated, $data);

            return $authenticated->loadMissing('customerProfile');
        }

        $email = strtolower(trim($data['customer_email']));
        $document = trim($data['customer_document']);

        $existing = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existing === null) {
            $existing = CustomerProfile::query()
                ->where('document', $document)
                ->with('user')
                ->first()
                ?->user;
        }

        if ($existing !== null) {
            $this->ensureCustomerProfile($existing, $data);

            return $existing->loadMissing('customerProfile');
        }

        return $this->createCustomerFromBooking($data);
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     customer_email: string
     * }  $data
     */
    private function createCustomerFromBooking(array $data): User
    {
        $email = strtolower(trim($data['customer_email']));
        $document = trim($data['customer_document']);
        [$firstName, $lastName] = $this->splitName(trim($data['customer_name']));

        if (CustomerProfile::query()->where('document', $document)->exists()) {
            throw ValidationException::withMessages([
                'customer_document' => 'Este documento ya está registrado con otra cuenta. Usa el correo asociado o inicia sesión.',
            ]);
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                'customer_email' => 'Este correo ya está registrado. Inicia sesión o usa otro correo.',
            ]);
        }

        $user = User::query()->create([
            'email' => $email,
            'password_hash' => Str::password(32),
            'status' => UserStatus::Pending,
        ]);

        $user->customerProfile()->create([
            'document' => $document,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim($data['customer_phone']),
        ]);

        $user->assignRole('Usuario');

        return $user->load('customerProfile');
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     customer_email: string
     * }  $data
     */
    private function ensureCustomerProfile(User $user, array $data): void
    {
        $profile = $user->customerProfile;
        [$firstName, $lastName] = $this->splitName(trim($data['customer_name']));
        $document = trim($data['customer_document']);
        $phone = trim($data['customer_phone']);

        if ($profile === null) {
            $documentTaken = CustomerProfile::query()
                ->where('document', $document)
                ->exists();

            if ($documentTaken) {
                throw ValidationException::withMessages([
                    'customer_document' => 'Este documento ya está registrado con otra cuenta.',
                ]);
            }

            $user->customerProfile()->create([
                'document' => $document,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);

            if (! $user->hasRole('Usuario') && ! $user->roles()->exists()) {
                $user->assignRole('Usuario');
            }

            return;
        }

        $updates = [];

        if ($profile->first_name === '' || $profile->first_name === null) {
            $updates['first_name'] = $firstName;
        }

        if ($profile->last_name === '' || $profile->last_name === null) {
            $updates['last_name'] = $lastName;
        }

        if (($profile->phone === null || $profile->phone === '') && $phone !== '') {
            $updates['phone'] = $phone;
        }

        if ($updates !== []) {
            $profile->forceFill($updates)->save();
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', $fullName, 2, PREG_SPLIT_NO_EMPTY) ?: [];

        $firstName = $parts[0] ?? 'Cliente';
        $lastName = $parts[1] ?? 'Motosworld';

        return [$firstName, $lastName];
    }
}
