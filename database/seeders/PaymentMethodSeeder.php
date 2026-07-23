<?php

namespace Database\Seeders;

use App\Enums\Payments\PaymentMethod as PaymentMethodEnum;
use App\Models\Payments\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'code' => PaymentMethodEnum::Card->value,
                'name' => 'Tarjeta',
                'description' => 'Pago con tarjeta de crédito o débito vía Culqi.',
                'sort_order' => 10,
            ],
            [
                'code' => PaymentMethodEnum::Yape->value,
                'name' => 'Yape',
                'description' => 'Pago con Yape vía Culqi.',
                'sort_order' => 20,
            ],
            [
                'code' => PaymentMethodEnum::Plin->value,
                'name' => 'Plin',
                'description' => 'Pago asíncrono con Plin vía Culqi.',
                'sort_order' => 30,
            ],
            [
                'code' => PaymentMethodEnum::PagoEfectivo->value,
                'name' => 'PagoEfectivo',
                'description' => 'Pago en efectivo / CIP vía Culqi PagoEfectivo.',
                'sort_order' => 40,
            ],
        ];

        foreach ($definitions as $definition) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ],
            );
        }
    }
}
