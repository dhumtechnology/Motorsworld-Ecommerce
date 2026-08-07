<?php

namespace App\Actions\Inventory;

use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Products\Inventory;
use App\Models\Products\InventoryMovement;
use App\Models\Products\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterInventoryMovementAction
{
    /**
     * @param  array{
     *     product_variant_id: int,
     *     type: InventoryMovementType,
     *     reason: InventoryMovementReason,
     *     quantity: int,
     *     notes?: string|null,
     *     order_id?: int|null,
     *     order_item_id?: int|null,
     *     created_by?: int|null,
     *     force?: bool,
     * }  $attributes
     */
    public function execute(array $attributes): InventoryMovement
    {
        $quantity = (int) $attributes['quantity'];

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad debe ser al menos 1.',
            ]);
        }

        return DB::transaction(function () use ($attributes, $quantity) {
            $variantId = (int) $attributes['product_variant_id'];
            /** @var InventoryMovementType $type */
            $type = $attributes['type'];

            $variant = ProductVariant::query()->with('product')->find($variantId);

            if ($variant === null) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'La variante de color no existe.',
                ]);
            }

            $inventory = Inventory::query()
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                $inventory = Inventory::query()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variantId,
                    'total_stock' => 0,
                    'available_stock' => 0,
                    'reserved_stock' => 0,
                ]);

                $inventory = Inventory::query()
                    ->where('id', $inventory->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($type === InventoryMovementType::Entry) {
                $inventory->available_stock = (int) $inventory->available_stock + $quantity;
                $inventory->total_stock = (int) $inventory->total_stock + $quantity;
            } else {
                $available = (int) $inventory->available_stock;
                $force = (bool) ($attributes['force'] ?? false);

                if (! $force && $quantity > $available) {
                    $label = $variant->sku;

                    throw ValidationException::withMessages([
                        'quantity' => "Stock insuficiente para «{$label}». Disponible: {$available}.",
                    ]);
                }

                $inventory->available_stock = max(0, $available - $quantity);
                $inventory->total_stock = max(0, (int) $inventory->total_stock - $quantity);
            }

            $inventory->save();

            return InventoryMovement::query()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variantId,
                'type' => $type,
                'reason' => $attributes['reason'],
                'quantity' => $quantity,
                'notes' => $attributes['notes'] ?? null,
                'order_id' => $attributes['order_id'] ?? null,
                'order_item_id' => $attributes['order_item_id'] ?? null,
                'created_by' => $attributes['created_by'] ?? null,
            ]);
        });
    }

    public function registerSaleExit(Order $order, OrderItem $item, ?int $createdBy = null): InventoryMovement
    {
        $variantId = (int) ($item->product_variant_id ?? 0);

        if ($variantId < 1) {
            $variantId = (int) ProductVariant::query()
                ->where('product_id', $item->product_id)
                ->orderBy('id')
                ->value('id');
        }

        return $this->execute([
            'product_variant_id' => $variantId,
            'type' => InventoryMovementType::Exit,
            'reason' => InventoryMovementReason::Sale,
            'quantity' => (int) $item->quantity,
            'notes' => 'Salida automática por venta — Orden #'.$order->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'created_by' => $createdBy,
            'force' => true,
        ]);
    }
}
