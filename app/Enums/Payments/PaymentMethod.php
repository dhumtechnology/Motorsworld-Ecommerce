<?php

namespace App\Enums\Payments;

enum PaymentMethod: string
{
    case Card = 'card';
    case Yape = 'yape';
    case Plin = 'plin';
    case PagoEfectivo = 'pagoefectivo';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Tarjeta',
            self::Yape => 'Yape',
            self::Plin => 'Plin',
            self::PagoEfectivo => 'PagoEfectivo',
        };
    }

    /**
     * Métodos que requieren token Culqi (tkn_ / ype_) generado en el frontend.
     */
    public function requiresCulqiToken(): bool
    {
        return match ($this) {
            self::Card, self::Yape => true,
            self::Plin, self::PagoEfectivo => false,
        };
    }

    /**
     * Métodos asíncronos que se confirman vía webhook (orden Culqi).
     */
    public function isAsync(): bool
    {
        return match ($this) {
            self::Plin, self::PagoEfectivo => true,
            self::Card, self::Yape => false,
        };
    }
}
