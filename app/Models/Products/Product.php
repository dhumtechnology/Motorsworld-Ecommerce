<?php

namespace App\Models\Products;

use App\Enums\Products\ProductStatus;
use App\Models\Cart\CartItem;
use App\Models\Orders\OrderItem;
use App\Services\Orders\ProductPricing;
use App\Services\Orders\ProductPricingService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'sku',
    'name',
    'description',
    'additional_information',
    'price_amount',
    'currency',
    'status',
    'image',
    'technical_sheet',
    'category_id',
    'model_id',
])]
class Product extends Model
{
    use SoftDeletes;

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
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
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
     * Imagen principal para listados (catálogo, cards).
     */
    public function catalogImageUrl(): ?string
    {
        if ($this->relationLoaded('variants')) {
            foreach ($this->variants as $variant) {
                $url = $variant->catalogImageUrl();
                if ($url) {
                    return $url;
                }
            }
        }

        if ($this->relationLoaded('images')) {
            $primary = $this->images->firstWhere('is_primary', true);

            return $primary?->path
                ?? $this->images->first()?->path
                ?? $this->attributes['image'] ?? null;
        }

        if ($this->relationLoaded('primaryImage') && $this->primaryImage !== null) {
            return $this->primaryImage->path;
        }

        $path = $this->primaryImage()->value('path');

        return $path ?? $this->attributes['image'] ?? null;
    }

    /**
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * @deprecated Use inventories()/variants(); kept for transitional accessors.
     *
     * @return HasOne<Inventory, $this>
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class)->oldestOfMany();
    }

    public function availableStockTotal(): int
    {
        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum(
                fn (ProductVariant $variant) => (int) ($variant->inventory?->available_stock ?? 0),
            );
        }

        if ($this->relationLoaded('inventories')) {
            return (int) $this->inventories->sum('available_stock');
        }

        return (int) $this->inventories()->sum('available_stock');
    }

    public function reservedStockTotal(): int
    {
        if ($this->relationLoaded('inventories')) {
            return (int) $this->inventories->sum('reserved_stock');
        }

        return (int) $this->inventories()->sum('reserved_stock');
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('id');
    }

    /**
     * @return HasOne<ProductOffer, $this>
     */
    public function activeOffer(): HasOne
    {
        return $this->hasOne(ProductOffer::class)->ofMany(
            ['offer_price_amount' => 'min'],
            fn (Builder $query) => $query->activeAt(),
        );
    }

    /**
     * @return HasMany<ProductOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class);
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

    public function activeOfferAt(?Carbon $at = null): ?ProductOffer
    {
        if ($this->relationLoaded('activeOffer')) {
            $offer = $this->getRelation('activeOffer');

            return $offer !== null && $offer->isActiveAt($at) ? $offer : null;
        }

        $at ??= now();

        if ($this->relationLoaded('offers')) {
            return $this->offers
                ->filter(fn (ProductOffer $offer) => $offer->isActiveAt($at))
                ->sortBy('offer_price_amount')
                ->first();
        }

        return $this->offers()
            ->activeAt($at)
            ->orderBy('offer_price_amount')
            ->first();
    }

    public function currentPricing(?Carbon $at = null): ProductPricing
    {
        return app(ProductPricingService::class)->resolve($this, $at);
    }

    public function currencySymbol(): string
    {
        return \App\Support\Currency::symbol($this->currency);
    }

    public function hasActiveOffer(?Carbon $at = null): bool
    {
        return $this->activeOfferAt($at) !== null;
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
            ->orderByRaw(
                'CASE WHEN (SELECT COALESCE(SUM(available_stock), 0) FROM inventory WHERE inventory.product_id = products.id) > 0 THEN 0 ELSE 1 END'
            )
            ->orderBy('products.id');
    }

    public function hasAvailableStock(): bool
    {
        return $this->availableStockTotal() > 0;
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
