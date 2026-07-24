<?php

namespace Database\Seeders;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const DEMO_ADMIN_COUNT = 2;

    private const DEMO_CUSTOMER_COUNT = 15;

    /**
     * Solo deja el admin fijo. Los demos quedan listos para reactivar.
     */
    public function run(): void
    {
        $this->seedFixedAdmin();

        // Desactivado temporalmente (reactivar cuando quieras datos demo):
        // $this->seedFixedCustomer();
        // $this->seedDemoAdmins();
        // $this->seedDemoCustomers();
    }

    private function seedFixedAdmin(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@motosworld.test'],
            [
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['Administrador']);
    }

    private function seedFixedCustomer(): void
    {
        $customer = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $customer->syncRoles(['Usuario']);

        CustomerProfile::query()->updateOrCreate(
            ['user_id' => $customer->id],
            [
                'document' => '12345678',
                'first_name' => 'Cliente',
                'last_name' => 'Prueba',
                'phone' => '+34 600 000 000',
                'gender' => 'other',
            ],
        );
    }

    private function seedDemoAdmins(): void
    {
        for ($i = 1; $i <= self::DEMO_ADMIN_COUNT; $i++) {
            $admin = User::query()->updateOrCreate(
                ['email' => sprintf('admin-demo-%02d@motosworld.test', $i)],
                [
                    'password_hash' => Hash::make('password'),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );

            $admin->syncRoles(['Administrador']);
        }
    }

    private function seedDemoCustomers(): void
    {
        $firstNames = [
            'Ana', 'Luis', 'María', 'Carlos', 'Sofía', 'Diego', 'Valeria', 'Jorge',
            'Camila', 'Andrés', 'Lucía', 'Pedro', 'Elena', 'Miguel', 'Paula',
        ];

        $lastNames = [
            'García', 'Rodríguez', 'López', 'Martínez', 'Pérez', 'Sánchez', 'Ramírez',
            'Torres', 'Flores', 'Vargas', 'Castillo', 'Morales', 'Rojas', 'Herrera', 'Jiménez',
        ];

        for ($i = 1; $i <= self::DEMO_CUSTOMER_COUNT; $i++) {
            $customer = User::query()->updateOrCreate(
                ['email' => sprintf('cliente-demo-%02d@motosworld.test', $i)],
                [
                    'password_hash' => Hash::make('password'),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );

            $customer->syncRoles(['Usuario']);

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $customer->id],
                [
                    'document' => sprintf('%08d', 20000000 + $i),
                    'first_name' => $firstNames[$i - 1],
                    'last_name' => $lastNames[$i - 1],
                    'phone' => sprintf('+51 9%02d %03d %03d', $i, 100 + $i, 200 + $i),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                ],
            );
        }
    }
}
