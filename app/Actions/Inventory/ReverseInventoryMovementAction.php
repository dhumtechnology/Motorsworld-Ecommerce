<?php

namespace App\Actions\Inventory;

use App\Enums\Inventory\InventoryMovementType;
use App\Models\Products\Inventory;
use App\Models\Products\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseInventoryMovementAction
{
    public function execute(InventoryMovement $movement): void
    {
        if (! $movement->isReversible()) {
            throw ValidationException::withMessages([
                'movement' => 'No se pueden revertir salidas por venta.',
            ]);
        }

        DB::transaction(function () use ($movement) {
            $movement = InventoryMovement::query()
                ->lockForUpdate()
                ->findOrFail($movement->id);

            if (! $movement->isReversible()) {
                throw ValidationException::withMessages([
                    'movement' => 'No se pueden revertir salidas por venta.',
                ]);
            }

            $inventory = Inventory::query()
                ->where('product_id', $movement->product_id)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                throw ValidationException::withMessages([
                    'movement' => 'No existe inventario para este producto.',
                ]);
            }

            $qty = (int) $movement->quantity;

            if ($movement->type === InventoryMovementType::Entry) {
                $available = (int) $inventory->available_stock;

                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'movement' => "No se puede revertir: stock disponible insuficiente ({$available}).",
                    ]);
                }

                $inventory->available_stock = $available - $qty;
                $inventory->total_stock = max(0, (int) $inventory->total_stock - $qty);
            } else {
                $inventory->available_stock = (int) $inventory->available_stock + $qty;
                $inventory->total_stock = (int) $inventory->total_stock + $qty;
            }

            $inventory->save();
            $movement->delete();
        });
    }
}
