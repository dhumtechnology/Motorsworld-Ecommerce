<?php

namespace Database\Seeders;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the superadmin account.
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
    }
}
