<?php

namespace App\Actions\Shop;

use App\Mail\ProfileUpdatedMail;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UpdateCustomerProfileAction
{
    /**
     * Actualiza solo datos editables. Email y documento nunca se modifican aquí.
     *
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     phone?: string|null
     * }  $attributes
     */
    public function execute(User $user, array $attributes): User
    {
        $user = DB::transaction(function () use ($user, $attributes): User {
            unset($attributes['email'], $attributes['document']);

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $attributes['first_name'],
                    'last_name' => $attributes['last_name'],
                    'phone' => $attributes['phone'] ?? null,
                ],
            );

            return $user->fresh(['customerProfile']);
        });

        $this->sendProfileUpdatedEmail($user);

        return $user;
    }

    private function sendProfileUpdatedEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new ProfileUpdatedMail($user));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
