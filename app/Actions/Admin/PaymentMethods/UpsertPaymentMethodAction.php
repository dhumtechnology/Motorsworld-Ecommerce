<?php

namespace App\Actions\Admin\PaymentMethods;

use App\Models\Payments\PaymentMethod;
use Illuminate\Support\Facades\DB;

class UpsertPaymentMethodAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?PaymentMethod $paymentMethod = null): PaymentMethod
    {
        return DB::transaction(function () use ($attributes, $paymentMethod) {
            if ($paymentMethod === null) {
                $paymentMethod = PaymentMethod::query()->create($attributes);
            } else {
                $paymentMethod->update($attributes);
            }

            return $paymentMethod->fresh();
        });
    }
}
