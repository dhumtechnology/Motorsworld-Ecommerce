<?php

namespace App\Actions\Shop;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResolveOrCreateCustomerAction
{
    /**
     * Resuelve un cliente autenticado o por correo/documento.
     * Si no existe, crea un usuario Pending (sin contraseña usable) con perfil.
     *
     * @param  array{
     *     first_name?: string,
     *     last_name?: string,
     *     customer_name?: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     customer_email: string
     * }  $data
     */
    public function execute(array $data, ?User $authenticated = null): User
    {
        if ($authenticated !== null) {
            $this->ensureCustomerProfile($authenticated, $data);

            return $authenticated->loadMissing('customerProfile');
        }

        $email = strtolower(trim($data['customer_email']));
        $document = trim($data['customer_document']);

        $existing = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existing === null) {
            $existing = CustomerProfile::query()
                ->where('document', $document)
                ->with('user')
                ->first()
                ?->user;
        }

        if ($existing !== null) {
            $this->ensureCustomerProfile($existing, $data);

            return $existing->loadMissing('customerProfile');
        }

        return $this->createPendingCustomer($data);
    }

    /**
     * @param  array{
     *     first_name?: string,
     *     last_name?: string,
     *     customer_name?: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     customer_email: string
     * }  $data
     */
    private function createPendingCustomer(array $data): User
    {
        $email = strtolower(trim($data['customer_email']));
        $document = trim($data['customer_document']);
        [$firstName, $lastName] = $this->resolveNames($data);

        if (CustomerProfile::query()->where('document', $document)->exists()) {
            throw ValidationException::withMessages([
                'customer_document' => 'Este documento ya está registrado con otra cuenta. Usa el correo asociado o inicia sesión.',
            ]);
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                'customer_email' => 'Este correo ya está registrado. Inicia sesión o usa otro correo.',
            ]);
        }

        $user = User::query()->create([
            'email' => $email,
            'password_hash' => Str::password(32),
            'status' => UserStatus::Pending,
        ]);

        $user->customerProfile()->create([
            'document' => $document,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim($data['customer_phone']),
        ]);

        $user->assignRole('Usuario');

        return $user->load('customerProfile');
    }

    /**
     * @param  array{
     *     first_name?: string,
     *     last_name?: string,
     *     customer_name?: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     customer_email: string
     * }  $data
     */
    private function ensureCustomerProfile(User $user, array $data): void
    {
        $profile = $user->customerProfile;
        [$firstName, $lastName] = $this->resolveNames($data);
        $document = trim($data['customer_document']);
        $phone = trim($data['customer_phone']);

        if ($profile === null) {
            $documentTaken = CustomerProfile::query()
                ->where('document', $document)
                ->exists();

            if ($documentTaken) {
                throw ValidationException::withMessages([
                    'customer_document' => 'Este documento ya está registrado con otra cuenta.',
                ]);
            }

            $user->customerProfile()->create([
                'document' => $document,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);

            if (! $user->hasRole('Usuario') && ! $user->roles()->exists()) {
                $user->assignRole('Usuario');
            }

            return;
        }

        $updates = [];

        if ($profile->first_name === '' || $profile->first_name === null) {
            $updates['first_name'] = $firstName;
        }

        if ($profile->last_name === '' || $profile->last_name === null) {
            $updates['last_name'] = $lastName;
        }

        if (($profile->phone === null || $profile->phone === '') && $phone !== '') {
            $updates['phone'] = $phone;
        }

        if ($updates !== []) {
            $profile->forceFill($updates)->save();
        }
    }

    /**
     * @param  array{first_name?: string, last_name?: string, customer_name?: string}  $data
     * @return array{0: string, 1: string}
     */
    private function resolveNames(array $data): array
    {
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            return [
                $firstName !== '' ? $firstName : 'Cliente',
                $lastName !== '' ? $lastName : 'Motosworld',
            ];
        }

        return $this->splitName(trim((string) ($data['customer_name'] ?? '')));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', $fullName, 2, PREG_SPLIT_NO_EMPTY) ?: [];

        $firstName = $parts[0] ?? 'Cliente';
        $lastName = $parts[1] ?? 'Motosworld';

        return [$firstName, $lastName];
    }
}
