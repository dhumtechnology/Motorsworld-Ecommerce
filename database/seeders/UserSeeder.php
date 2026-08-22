<?php

namespace Database\Seeders;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Superadmin + clientes demo (rol Usuario) para pedidos y citas.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@motoworld.test'],
            [
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['Administrador']);

        $customers = [
            [
                'email' => 'cliente1@motoworld.test',
                'document' => '40111222',
                'first_name' => 'Ana',
                'last_name' => 'García',
                'phone' => '999111222',
            ],
            [
                'email' => 'cliente2@motoworld.test',
                'document' => '40333444',
                'first_name' => 'Luis',
                'last_name' => 'Torres',
                'phone' => '999333444',
            ],
            [
                'email' => 'cliente3@motoworld.test',
                'document' => '40555666',
                'first_name' => 'María',
                'last_name' => 'Vargas',
                'phone' => '999555666',
            ],
        ];

        foreach ($customers as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'password_hash' => Hash::make('password'),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles(['Usuario']);

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'document' => $data['document'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'gender' => 'other',
                ],
            );
        }
    }
}
