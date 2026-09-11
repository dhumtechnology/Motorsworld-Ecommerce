<?php

namespace App\Enums\Products;

enum StockAvailability: string
{
    case Store = 'store';
    case Warehouse = 'warehouse';
    case OnOrder = 'on_order';
    case Unavailable = 'unavailable';
    case ComingSoon = 'coming_soon';

    public function label(): string
    {
        return match ($this) {
            self::Store => 'Tienda',
            self::Warehouse => 'Almacén',
            self::OnOrder => 'A pedido',
            self::Unavailable => 'No disponible',
            self::ComingSoon => 'Próximamente',
        };
    }

    /**
     * @return list<self>
     */
    public static function casesInAdminOrder(): array
    {
        return [
            self::Store,
            self::Warehouse,
            self::OnOrder,
            self::Unavailable,
            self::ComingSoon,
        ];
    }
}
