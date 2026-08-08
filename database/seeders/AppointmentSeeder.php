<?php

namespace Database\Seeders;

use App\Models\Appointments\ServicePackage;
use App\Models\Appointments\ServiceType;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Seed service types and their packages (idempotent).
     */
    public function run(): void
    {
        $serviceTypes = $this->seedServiceTypes();
        $this->seedServicePackages($serviceTypes);
    }

    /**
     * @return array<string, ServiceType>
     */
    private function seedServiceTypes(): array
    {
        $definitions = [
            'Mantenimiento preventivo' => [
                'description' => 'Revisión periódica para mantener tu moto en óptimas condiciones.',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTh_mUP8eGXNljBnZ34mtOKfTzDOgzHes4BjoxEAow2UmKsTIJlIOqJZ5I&s=10',
            ],
            'Cambio de aceite' => [
                'description' => 'Cambio de aceite y filtro con productos de calidad.',
                'image' => 'https://euroshop.com.pe/wp-content/uploads/2026/03/mantenimiento-preventivo-moto-deportiva.jpg',
            ],
            'Revisión de frenos' => [
                'description' => 'Inspección y servicio del sistema de frenos.',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRA6dxlMtfcT9sSD6AGfbHnqpIxloBPZIDws3UeRFuQDWPaymqtjAZTDV8&s=10',
            ],
            'Alineamiento y balanceo' => [
                'description' => 'Ajuste de dirección y balanceo de ruedas.',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcShYUXXf4v9Gyopqdg10S16PEaIw-_6dZ46FSVUgPX1VSv3jGqBlLc8ggj1&s=10',
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
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQFrHeWfL4kYXcgPdbsAiU2svw3wmCQFnQkvKi4G7xF4MVDhdtZZckMcnc&s=10',
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
}
