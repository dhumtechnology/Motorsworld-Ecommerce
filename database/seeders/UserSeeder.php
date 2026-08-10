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
     * Seed admin + store customers with roles (idempotent).
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

        $demoCustomer = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'password_hash' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
        $demoCustomer->syncRoles(['Usuario']);
        $this->ensureCustomerProfile($demoCustomer, [
            'first_name' => 'Cliente',
            'last_name' => 'Demo',
            'document' => '12345678',
            'phone' => '999888777',
        ]);

        if (User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'administrador'))->count() < 2) {
            User::factory()->count(1)->administrador()->create();
        }

        $customerCount = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'usuario'))
            ->count();

        if ($customerCount < 8) {
            User::factory()
                ->count(8 - $customerCount)
                ->usuario()
                ->create();
        }
    }

    /**
     * @param  array{first_name: string, last_name: string, document: string, phone: string}  $data
     */
    private function ensureCustomerProfile(User $user, array $data): void
    {
        CustomerProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $data,
        );
    }
}
