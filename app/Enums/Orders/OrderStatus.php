<?php

namespace App\Enums\Orders;

enum OrderStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Creada',
            self::Paid => 'Pagada',
            self::Processing => 'En proceso',
            self::Shipped => 'Enviada',
            self::Delivered => 'Entregada',
            self::Cancelled => 'Cancelada',
            self::Refunded => 'Reembolsada',
        };
    }
}
