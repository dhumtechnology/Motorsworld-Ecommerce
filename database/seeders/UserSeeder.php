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
     * Seed application users with fixed accounts and factory-generated customers.
     */
    public function run(): void
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

        User::factory()
            ->count(2)
            ->administrador()
            ->create();

        User::factory()
            ->count(15)
            ->usuario()
            ->create();
    }
}
