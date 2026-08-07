<?php

namespace App\Models\Orders;

use App\Models\Products\Product;
use App\Models\Products\ProductOffer;
use App\Models\Products\ProductVariant;
use App\Services\Orders\OrderItemPricingService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'order_id',
    'product_id',
    'product_variant_id',
    'product_offer_id',
    'quantity',
    'unit_price',
    'list_unit_price',
    'currency',
])]
class OrderItem extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    /**
     * Build order line attributes with catalog/offer pricing frozen at checkout time.
     *
     * @return array<string, mixed>
     */
    public static function pricingAttributesFor(
        Product $product,
        int $quantity,
        ?Carbon $at = null,
        ?ProductVariant $variant = null,
    ): array {
        return app(OrderItemPricingService::class)->attributesFor($product, $quantity, $at, $variant);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<ProductOffer, $this>
     */
    public function productOffer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class);
    }

    public function hadOffer(): bool
    {
        return $this->product_offer_id !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'list_unit_price' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }
}
