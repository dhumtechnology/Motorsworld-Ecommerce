<?php

namespace Database\Seeders;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Usuario administrador del panel.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@motoworld.test'],
            [
                'password_hash' => 'password',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['Administrador']);
    }
}
