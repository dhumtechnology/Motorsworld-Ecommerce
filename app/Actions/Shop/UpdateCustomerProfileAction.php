<?php

namespace App\Actions\Shop;

use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;

class UpdateCustomerProfileAction
{
    /**
     * @param  array{
     *     email: string,
     *     document: string,
     *     first_name: string,
     *     last_name: string,
     *     phone?: string|null
     * }  $attributes
     */
    public function execute(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $user->forceFill([
                'email' => $attributes['email'],
            ])->save();

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'document' => $attributes['document'],
                    'first_name' => $attributes['first_name'],
                    'last_name' => $attributes['last_name'],
                    'phone' => $attributes['phone'] ?? null,
                ],
            );

            return $user->fresh(['customerProfile']);
        });
    }
}
