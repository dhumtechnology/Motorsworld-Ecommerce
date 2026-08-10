<?php

namespace Database\Seeders;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use App\Models\Appointments\ServicePackage;
use App\Models\Appointments\ServiceType;
use App\Models\Auth\User;
use App\Models\Products\Brand;
use App\Models\Products\VehicleModel;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    private const SEED_COMMENT = '[AppointmentSeeder] Reserva demo';

    /**
     * Seed service types, packages and sample appointments (idempotent).
     */
    public function run(): void
    {
        $serviceTypes = $this->seedServiceTypes();
        $this->seedServicePackages($serviceTypes);
        $this->seedAppointments($serviceTypes);
    }

    /**
     * @return array<string, ServiceType>
     */
    private function seedServiceTypes(): array
    {
        $definitions = [
            'Mantenimiento preventivo' => [
                'description' => 'Revisión periódica para mantener tu moto en óptimas condiciones.',
                'image' => '/images/services/mantenimiento-preventivo.png',
            ],
            'Cambio de aceite' => [
                'description' => 'Cambio de aceite y filtro con productos de calidad.',
                'image' => '/images/services/cambio-de-aceite.png',
            ],
            'Revisión de frenos' => [
                'description' => 'Inspección y servicio del sistema de frenos.',
                'image' => '/images/services/revision-de-frenos.png',
            ],
            'Alineamiento y balanceo' => [
                'description' => 'Ajuste de dirección y balanceo de ruedas.',
                'image' => '/images/services/alineamiento-y-balanceo.png',
            ],
            'Diagnóstico electrónico' => [
                'description' => 'Escaneo y análisis de fallas del sistema electrónico.',
                'image' => '/images/services/diagnostico-electronico.png',
            ],
            'Cambio de batería' => [
                'description' => 'Instalación y prueba de batería para tu moto.',
                'image' => '/images/services/cambio-de-bateria.png',
            ],
            'Servicio de cadena' => [
                'description' => 'Limpieza, lubricación y ajuste de cadena.',
                'image' => '/images/services/servicio-de-cadena.png',
            ],
            'Lavado y detailing' => [
                'description' => 'Lavado exterior y detailing para dejar tu moto impecable.',
                'image' => '/images/services/lavado-y-detailing.png',
            ],
        ];

        $types = [];

        foreach ($definitions as $name => $data) {
            $types[$name] = ServiceType::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $data['description'],
                    'image' => $data['image'],
                ],
            );
        }

        return $types;
    }

    /**
     * @param  array<string, ServiceType>  $serviceTypes
     */
    private function seedServicePackages(array $serviceTypes): void
    {
        $packages = [
            'Mantenimiento preventivo' => [
                ['name' => 'Básico', 'price' => 89.90, 'description' => 'Inspección general y ajustes'],
                ['name' => 'Completo', 'price' => 149.90, 'description' => 'Inspección + fluidos + frenos'],
            ],
            'Cambio de aceite' => [
                ['name' => 'Aceite mineral', 'price' => 59.90, 'description' => 'Cambio de aceite mineral + filtro'],
                ['name' => 'Aceite sintético', 'price' => 89.90, 'description' => 'Cambio de aceite sintético + filtro'],
            ],
            'Revisión de frenos' => [
                ['name' => 'Inspección', 'price' => 49.90, 'description' => 'Revisión de pastillas y discos'],
                ['name' => 'Servicio completo', 'price' => 119.90, 'description' => 'Cambio de pastillas + purga'],
            ],
            'Alineamiento y balanceo' => [
                ['name' => 'Balanceo', 'price' => 39.90, 'description' => 'Balanceo de ruedas'],
                ['name' => 'Alineamiento + balanceo', 'price' => 79.90, 'description' => 'Paquete completo'],
            ],
            'Diagnóstico electrónico' => [
                ['name' => 'Escaneo básico', 'price' => 69.90, 'description' => 'Lectura de códigos'],
                ['name' => 'Diagnóstico avanzado', 'price' => 129.90, 'description' => 'Escaneo + reporte técnico'],
            ],
            'Cambio de batería' => [
                ['name' => 'Instalación', 'price' => 29.90, 'description' => 'Instalación de batería (batería aparte)'],
                ['name' => 'Instalación + prueba', 'price' => 49.90, 'description' => 'Instalación y prueba del sistema eléctrico'],
            ],
            'Servicio de cadena' => [
                ['name' => 'Limpieza y lubricación', 'price' => 34.90, 'description' => 'Limpieza + grasa'],
                ['name' => 'Ajuste completo', 'price' => 54.90, 'description' => 'Limpieza, lubricación y tensión'],
            ],
            'Lavado y detailing' => [
                ['name' => 'Lavado express', 'price' => 24.90, 'description' => 'Lavado exterior'],
                ['name' => 'Detailing premium', 'price' => 79.90, 'description' => 'Lavado + detailing completo'],
            ],
        ];

        foreach ($packages as $typeName => $items) {
            $type = $serviceTypes[$typeName] ?? null;
            if ($type === null) {
                continue;
            }

            foreach ($items as $item) {
                ServicePackage::query()->updateOrCreate(
                    [
                        'service_type_id' => $type->id,
                        'name' => $item['name'],
                    ],
                    [
                        'description' => $item['description'],
                        'price' => $item['price'],
                        'currency' => 'PEN',
                        'duration_minutes' => 60,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, ServiceType>  $serviceTypes
     */
    private function seedAppointments(array $serviceTypes): void
    {
        Appointment::query()
            ->where('comments', self::SEED_COMMENT)
            ->delete();

        $customers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'usuario'))
            ->with('customerProfile')
            ->orderBy('id')
            ->limit(5)
            ->get();

        if ($customers->isEmpty()) {
            $this->command?->warn('AppointmentSeeder: no hay clientes. Ejecute UserSeeder primero.');

            return;
        }

        $brand = Brand::query()->where('name', 'Yamaha')->first()
            ?? Brand::query()->orderBy('id')->first();
        $model = $brand
            ? VehicleModel::query()->where('brand_id', $brand->id)->orderBy('id')->first()
            : null;

        $definitions = [
            [
                'type' => 'Mantenimiento preventivo',
                'package' => 'Completo',
                'status' => AppointmentStatus::Pending,
                'days' => 2,
            ],
            [
                'type' => 'Cambio de aceite',
                'package' => 'Aceite sintético',
                'status' => AppointmentStatus::Accepted,
                'days' => 1,
            ],
            [
                'type' => 'Revisión de frenos',
                'package' => 'Servicio completo',
                'status' => AppointmentStatus::InProgress,
                'days' => 0,
            ],
            [
                'type' => 'Lavado y detailing',
                'package' => 'Detailing premium',
                'status' => AppointmentStatus::Attended,
                'days' => -3,
            ],
            [
                'type' => 'Diagnóstico electrónico',
                'package' => 'Escaneo básico',
                'status' => AppointmentStatus::Cancelled,
                'days' => -1,
            ],
        ];

        foreach ($definitions as $index => $definition) {
            $type = $serviceTypes[$definition['type']] ?? null;
            if ($type === null) {
                continue;
            }

            $package = ServicePackage::query()
                ->where('service_type_id', $type->id)
                ->where('name', $definition['package'])
                ->first();

            if ($package === null) {
                continue;
            }

            $customer = $customers[$index % $customers->count()];
            $profile = $customer->customerProfile;
            $appointmentAt = now()->addDays($definition['days'])->setTime(10 + $index, 0);

            Appointment::query()->create([
                'user_id' => $customer->id,
                'customer_name' => trim(($profile?->first_name ?? 'Cliente').' '.($profile?->last_name ?? 'Demo')),
                'customer_document' => $profile?->document,
                'customer_phone' => $profile?->phone,
                'customer_email' => $customer->email,
                'appointment_at' => $appointmentAt,
                'brand_id' => $brand?->id,
                'vehicle_model_id' => $model?->id,
                'km' => 12000 + ($index * 1500),
                'plate' => 'ABC-'.str_pad((string) (100 + $index), 3, '0', STR_PAD_LEFT),
                'service_type_id' => $type->id,
                'service_package_id' => $package->id,
                'comments' => self::SEED_COMMENT,
                'status' => $definition['status'],
                'charged_amount' => $definition['status'] === AppointmentStatus::Attended
                    ? $package->price
                    : null,
                'charged_currency' => $definition['status'] === AppointmentStatus::Attended
                    ? 'PEN'
                    : null,
                'attended_at' => $definition['status'] === AppointmentStatus::Attended
                    ? $appointmentAt->copy()->addHour()
                    : null,
                'cancellation_reason' => $definition['status'] === AppointmentStatus::Cancelled
                    ? 'Cliente reprogramó'
                    : null,
            ]);
        }
    }
}
