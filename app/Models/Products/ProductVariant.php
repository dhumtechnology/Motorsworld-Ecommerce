<?php

namespace App\Models\Products;

use App\Models\Cart\CartItem;
use App\Models\Orders\OrderItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'product_id',
    'sku',
    'name',
    'is_active',
])]
class ProductVariant extends Model
{
    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsToMany<Color, $this>
     */
    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'color_product_variant')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * @return HasOne<Inventory, $this>
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('id');
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

    public function colorLabel(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->relationLoaded('colors') && $this->colors->isNotEmpty()) {
            return $this->colors->pluck('name')->implode(' / ');
        }

        return 'Estándar';
    }

    public function catalogImageUrl(): ?string
    {
        if ($this->relationLoaded('images')) {
            $primary = $this->images->firstWhere('is_primary', true);

            return $primary?->path ?? $this->images->first()?->path;
        }

        if ($this->relationLoaded('primaryImage') && $this->primaryImage !== null) {
            return $this->primaryImage->path;
        }

        return $this->primaryImage()->value('path');
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
            'is_active' => 'boolean',
        ];
    }
}
