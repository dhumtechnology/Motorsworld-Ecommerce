<?php

namespace App\Models\Products;

use App\Enums\Products\ProductStatus;
use App\Models\Cart\CartItem;
use App\Models\Orders\OrderItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'sku',
    'description',
    'price_amount',
    'currency',
    'status',
    'image',
    'category_id',
    'model_id',
])]
class Product extends Model
{
    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    /**
     * @return HasOne<Inventory, $this>
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ProductStatus::Active);
    }

    /**
     * In-stock products first, then out-of-stock; within each group by id.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeCatalogOrder(Builder $query): void
    {
        $query
            ->leftJoin('inventory', 'products.id', '=', 'inventory.product_id')
            ->select('products.*')
            ->orderByRaw('CASE WHEN COALESCE(inventory.available_stock, 0) > 0 THEN 0 ELSE 1 END')
            ->orderBy('products.id');
    }

    public function hasAvailableStock(): bool
    {
        return ($this->inventory?->available_stock ?? 0) > 0;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'status' => ProductStatus::class,
        ];
    }
}
