<?php

namespace App\Actions\Shop;

use App\Enums\Auth\UserStatus;
use App\Mail\WelcomeCustomerMail;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

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
        $user = DB::transaction(function () use ($email, $password, $document, $firstName, $lastName, $phone): User {
            $email = strtolower(trim($email));

            $pending = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('status', UserStatus::Pending)
                ->first();

            if ($pending !== null) {
                return $this->activatePendingCustomer(
                    $pending,
                    $password,
                    $document,
                    $firstName,
                    $lastName,
                    $phone,
                );
            }

            if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Este correo ya está registrado.',
                ]);
            }

            if (CustomerProfile::query()->where('document', $document)->exists()) {
                throw ValidationException::withMessages([
                    'document' => 'Este documento ya está registrado.',
                ]);
            }

            $user = User::query()->create([
                'email' => $email,
                'password_hash' => $password,
                'status' => UserStatus::Active,
            ]);

            $user->customerProfile()->create([
                'document' => $document,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);

            $user->assignRole('Usuario');

            return $user->load('customerProfile', 'roles');
        });

        $this->sendWelcomeEmail($user);

        return $user;
    }

    private function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new WelcomeCustomerMail($user));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function activatePendingCustomer(
        User $user,
        string $password,
        string $document,
        string $firstName,
        string $lastName,
        ?string $phone,
    ): User {
        $profileOwner = CustomerProfile::query()
            ->where('document', $document)
            ->first();

        if ($profileOwner !== null && (int) $profileOwner->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'document' => 'Este documento ya está registrado con otra cuenta.',
            ]);
        }

        $user->forceFill([
            'password_hash' => $password,
            'status' => UserStatus::Active,
        ])->save();

        $profile = $user->customerProfile;

        if ($profile === null) {
            $user->customerProfile()->create([
                'document' => $document,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);
        } else {
            $profile->forceFill([
                'document' => $document,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ])->save();
        }

        if (! $user->hasRole('Usuario')) {
            $user->assignRole('Usuario');
        }

        return $user->load('customerProfile', 'roles');
    }
}
