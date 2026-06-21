<?php

namespace Database\Seeders;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create([
            'email' => 'admin@motosworld.test',
            'status' => UserStatus::Active,
        ]);

        $admin->assignRole('Administrator');

        User::factory()->create([
            'email' => 'test@example.com',
            'status' => UserStatus::Active,
        ])->assignRole('Customer');
    }
}
