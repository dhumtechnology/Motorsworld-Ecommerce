<?php

namespace App\Actions\Shop;

use App\Enums\Orders\OrderStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Services\Products\ProductOfferPresenter;
use App\Support\QueryResultCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetHomePageDataAction
{
    private const MOTOS_CATEGORY = 'MOTOS';

    private const POPULAR_LIMIT = 4;

    public function __construct(
        private readonly ProductOfferPresenter $offerPresenter,
    ) {}

    /**
     * @return array{
     *     popularProducts: Collection<int, Product>,
     *     brands: Collection<int, Brand>,
     *     categories: Collection<int, Category>
     * }
     */
    public function execute(): array
    {
        return [
            'popularProducts' => $this->popularProducts(),
            'brands' => $this->brands(),
            'categories' => $this->categories(),
        ];
    }

    /**
     * @return Collection<int, Brand>
     */
    private function brands(): Collection
    {
        return Brand::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'image']);
    }

    /**
     * Categorías con imagen para el home (MOTOS primero).
     *
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        return Category::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderByRaw('CASE WHEN UPPER(name) = ? THEN 0 ELSE 1 END', [self::MOTOS_CATEGORY])
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'image']);
    }

    /**
     * @return Collection<int, Product>
     */
    private function popularProducts(): Collection
    {
        $motosCategoryId = $this->motosCategoryId();

        $rankedIds = Product::query()
            ->toBase()
            ->select('products.id')
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('products.status', ProductStatus::Active->value)
            ->whereNotIn('orders.status', [
                OrderStatus::Cancelled->value,
                OrderStatus::Refunded->value,
            ])
            ->when(
                $motosCategoryId !== null,
                fn ($q) => $q->where('products.category_id', '!=', $motosCategoryId),
            )
            ->groupBy('products.id')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(self::POPULAR_LIMIT)
            ->pluck('id');

        if ($rankedIds->isEmpty()) {
            $fallback = Product::query()
                ->active()
                ->when(
                    $motosCategoryId !== null,
                    fn (Builder $q) => $q->where('category_id', '!=', $motosCategoryId),
                )
                ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
                ->latest('id')
                ->limit(self::POPULAR_LIMIT)
                ->get();

            return $fallback->map(fn (Product $product) => $this->offerPresenter->apply($product));
        }

        $orderById = $rankedIds->values()->all();

        return Product::query()
            ->whereIn('id', $orderById)
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
            ->get()
            ->sortBy(fn (Product $product): int => array_search($product->id, $orderById, true))
            ->values()
            ->map(fn (Product $product) => $this->offerPresenter->apply($product));
    }

    private function motosCategoryId(): ?int
    {
        return QueryResultCache::remember(
            'catalog.motos_category_id',
            function (): ?int {
                $id = Category::query()
                    ->whereRaw('UPPER(name) = ?', [self::MOTOS_CATEGORY])
                    ->value('id');

                return $id !== null ? (int) $id : null;
            },
        );
    }
}
