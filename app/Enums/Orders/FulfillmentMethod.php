<?php

namespace App\Enums\Orders;

enum FulfillmentMethod: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Recojo en tienda',
            self::Delivery => 'Delivery',
        };
    }
}
