<?php

namespace App\Actions\Shop;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;

class RegisterCustomerAction
{
    public function execute(
        string $email,
        string $password,
        string $document,
        string $firstName,
        string $lastName,
        ?string $phone = null,
    ): User {
        return DB::transaction(function () use ($email, $password, $document, $firstName, $lastName, $phone): User {
            $user = User::query()->create([
                'email' => $email,
                'password_hash' => $password,
                'status' => UserStatus::Active,
            ]);

            CustomerProfile::query()->create([
                'user_id' => $user->id,
                'document' => $document,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);

            $user->assignRole('Usuario');

            return $user->load('customerProfile', 'roles');
        });
    }
}
