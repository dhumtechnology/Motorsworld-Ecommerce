<?php

namespace Database\Seeders;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use App\Models\Appointments\ServiceType;
use App\Models\Auth\User;
use App\Models\Products\VehicleModel;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Seed service types and demo appointments (idempotent).
     */
    public function run(): void
    {
        $serviceTypes = $this->seedServiceTypes();
        $this->seedAppointments($serviceTypes);
    }

    /**
     * @return array<string, ServiceType>
     */
    private function seedServiceTypes(): array
    {
        $definitions = [
            'Mantenimiento preventivo',
            'Cambio de aceite',
            'Revisión de frenos',
            'Alineamiento y balanceo',
            'Diagnóstico electrónico',
            'Cambio de batería',
            'Servicio de cadena',
            'Lavado y detailing',
        ];

        $types = [];

        foreach ($definitions as $name) {
            $types[$name] = ServiceType::query()->updateOrCreate(
                ['name' => $name],
                ['image' => null],
            );
        }

        return $types;
    }

    /**
     * @param  array<string, ServiceType>  $serviceTypes
     */
    private function seedAppointments(array $serviceTypes): void
    {
        $customers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Usuario'))
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($customers->isEmpty()) {
            $this->command?->warn('AppointmentSeeder: no hay clientes. Ejecute UserSeeder primero.');

            return;
        }

        $models = VehicleModel::query()->orderBy('id')->limit(10)->get();
        $typeList = array_values($serviceTypes);
        $statuses = AppointmentStatus::cases();

        $definitions = [
            ['days' => 0, 'hour' => 9, 'plate' => 'ABC-123', 'km' => 12500, 'status' => AppointmentStatus::Pending],
            ['days' => 0, 'hour' => 11, 'plate' => 'XYZ-456', 'km' => 8200, 'status' => AppointmentStatus::InProgress],
            ['days' => 0, 'hour' => 15, 'plate' => 'MOT-789', 'km' => 21000, 'status' => AppointmentStatus::Pending],
            ['days' => 1, 'hour' => 10, 'plate' => 'PER-101', 'km' => 5400, 'status' => AppointmentStatus::Pending],
            ['days' => 1, 'hour' => 14, 'plate' => 'LIM-202', 'km' => 17800, 'status' => AppointmentStatus::Attended],
            ['days' => 2, 'hour' => 9, 'plate' => 'CUS-303', 'km' => 9300, 'status' => AppointmentStatus::Pending],
            ['days' => -1, 'hour' => 16, 'plate' => 'ARE-404', 'km' => 30100, 'status' => AppointmentStatus::Attended],
            ['days' => -2, 'hour' => 10, 'plate' => 'TRU-505', 'km' => 11200, 'status' => AppointmentStatus::Absent],
            ['days' => 3, 'hour' => 12, 'plate' => 'PIU-606', 'km' => 6700, 'status' => AppointmentStatus::Pending],
            ['days' => 5, 'hour' => 8, 'plate' => 'ICA-707', 'km' => 15400, 'status' => AppointmentStatus::Cancelled],
            ['days' => 7, 'hour' => 13, 'plate' => 'TAC-808', 'km' => 4200, 'status' => AppointmentStatus::Pending],
            ['days' => -3, 'hour' => 11, 'plate' => 'HUA-909', 'km' => 25600, 'status' => AppointmentStatus::Attended],
        ];

        foreach ($definitions as $index => $definition) {
            $customer = $customers[$index % $customers->count()];
            $serviceType = $typeList[$index % count($typeList)];
            $model = $models->isNotEmpty() ? $models[$index % $models->count()] : null;
            $appointmentAt = now()->startOfDay()->addDays($definition['days'])->setTime($definition['hour'], 0);

            Appointment::query()->updateOrCreate(
                [
                    'user_id' => $customer->id,
                    'appointment_at' => $appointmentAt,
                    'plate' => $definition['plate'],
                ],
                [
                    'vehicle_model_id' => $model?->id,
                    'km' => $definition['km'],
                    'service_type_id' => $serviceType->id,
                    'comments' => 'Reserva demo generada por AppointmentSeeder.',
                    'status' => $definition['status'] ?? $statuses[$index % count($statuses)],
                ],
            );
        }
    }
}
