<?php

namespace App\Enums\Inventory;

enum InventoryMovementReason: string
{
    case Purchase = 'purchase';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Manual = 'manual';
    case Sale = 'sale';
    case Damage = 'damage';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Compra / reposición',
            self::Return => 'Devolución',
            self::Adjustment => 'Ajuste',
            self::Manual => 'Manual',
            self::Sale => 'Venta',
            self::Damage => 'Merma / daño',
        };
    }

    /**
     * @return list<self>
     */
    public static function forEntries(): array
    {
        return [
            self::Purchase,
            self::Return,
            self::Adjustment,
            self::Manual,
        ];
    }

    /**
     * @return list<self>
     */
    public static function forManualExits(): array
    {
        return [
            self::Manual,
            self::Damage,
            self::Adjustment,
            self::Return,
        ];
    }
}
