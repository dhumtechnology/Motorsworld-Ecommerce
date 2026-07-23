<?php

namespace Database\Seeders;

use App\Actions\Inventory\RegisterInventoryMovementAction;
use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use App\Models\Products\InventoryMovement;
use App\Models\Products\Product;
use Illuminate\Database\Seeder;

class InventoryMovementSeeder extends Seeder
{
    private const SEED_TAG = '[InventoryMovementSeeder]';

    public function run(): void
    {
        $this->resetPreviousSeedMovements();

        $register = app(RegisterInventoryMovementAction::class);

        $entries = [
            ['sku' => 'MW-ACC-001', 'qty' => 20, 'reason' => InventoryMovementReason::Purchase, 'notes' => 'Reposición inicial de cascos'],
            ['sku' => 'MW-LUB-001', 'qty' => 30, 'reason' => InventoryMovementReason::Purchase, 'notes' => 'Compra de lubricantes'],
            ['sku' => 'MW-REP-001', 'qty' => 15, 'reason' => InventoryMovementReason::Purchase, 'notes' => 'Ingreso de filtros'],
            ['sku' => 'MW-BAT-001', 'qty' => 8, 'reason' => InventoryMovementReason::Return, 'notes' => 'Devolución de cliente'],
        ];

        foreach ($entries as $entry) {
            $productId = Product::query()->where('sku', $entry['sku'])->value('id');

            if ($productId === null) {
                continue;
            }

            $register->execute([
                'product_id' => (int) $productId,
                'type' => InventoryMovementType::Entry,
                'reason' => $entry['reason'],
                'quantity' => $entry['qty'],
                'notes' => self::SEED_TAG.' '.$entry['notes'],
            ]);
        }

        $manualExits = [
            ['sku' => 'MW-ACC-002', 'qty' => 2, 'reason' => InventoryMovementReason::Damage, 'notes' => 'Guantes dañados en almacén'],
            ['sku' => 'MW-LUB-002', 'qty' => 1, 'reason' => InventoryMovementReason::Manual, 'notes' => 'Muestra de exhibición'],
        ];

        foreach ($manualExits as $exit) {
            $productId = Product::query()->where('sku', $exit['sku'])->value('id');

            if ($productId === null) {
                continue;
            }

            try {
                $register->execute([
                    'product_id' => (int) $productId,
                    'type' => InventoryMovementType::Exit,
                    'reason' => $exit['reason'],
                    'quantity' => $exit['qty'],
                    'notes' => self::SEED_TAG.' '.$exit['notes'],
                ]);
            } catch (\Illuminate\Validation\ValidationException) {
                // Stock insuficiente en reseed; se omite.
            }
        }
    }

    private function resetPreviousSeedMovements(): void
    {
        $movements = InventoryMovement::query()
            ->where('notes', 'like', self::SEED_TAG.'%')
            ->orderByDesc('id')
            ->get();

        $reverse = app(\App\Actions\Inventory\ReverseInventoryMovementAction::class);

        foreach ($movements as $movement) {
            try {
                $reverse->execute($movement);
            } catch (\Throwable) {
                $movement->delete();
            }
        }
    }
}
