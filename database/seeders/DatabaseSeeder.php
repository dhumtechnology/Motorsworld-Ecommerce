<?php

namespace Database\Seeders;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CatalogSeeder::class,
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@motosworld.test'],
            [
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
        $admin->assignRole('Administrator');

        $customer = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
        $customer->assignRole('Customer');
    }
}
