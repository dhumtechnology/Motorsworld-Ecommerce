<?php

namespace App\Models\Products;

use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use App\Models\Auth\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'type',
    'reason',
    'quantity',
    'notes',
    'order_id',
    'order_item_id',
    'created_by',
])]
class InventoryMovement extends Model
{
    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSaleExit(): bool
    {
        return $this->type === InventoryMovementType::Exit
            && $this->reason === InventoryMovementReason::Sale;
    }

    public function isReversible(): bool
    {
        return ! $this->isSaleExit();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'reason' => InventoryMovementReason::class,
            'quantity' => 'integer',
        ];
    }
}
