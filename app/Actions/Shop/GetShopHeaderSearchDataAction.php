<?php

namespace App\Actions\Shop;

use App\Enums\Orders\OrderStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Orders\OrderItem;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Services\Products\ProductOfferPresenter;
use App\Support\QueryResultCache;
use Illuminate\Support\Collection;

class GetShopHeaderSearchDataAction
{
    private const MOTOS_CATEGORY = 'MOTOS';

    private const RECOMMENDED_LIMIT = 6;

    public function __construct(
        private readonly ProductOfferPresenter $offerPresenter,
    ) {}

    /**
     * @return array{
     *     searchCategories: list<array{id: int, name: string, href: string, image: ?string, is_motos: bool}>,
     *     searchRecommendedProducts: Collection<int, Product>
     * }
     */
    public function execute(): array
    {
        return [
            'searchCategories' => $this->categories(),
            'searchRecommendedProducts' => $this->recommendedProducts(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, href: string, image: ?string, is_motos: bool}>
     */
    private function categories(): array
    {
        return QueryResultCache::remember(
            'shop.header.search_categories.v2',
            function (): array {
                $categories = Category::query()
                    ->orderByRaw('CASE WHEN UPPER(name) = ? THEN 0 ELSE 1 END', [self::MOTOS_CATEGORY])
                    ->orderBy('name')
                    ->get(['id', 'name', 'image']);

                return $categories->map(function (Category $category): array {
                    $isMotos = strtoupper(trim($category->name)) === self::MOTOS_CATEGORY;

                    return [
                        'id' => (int) $category->id,
                        'name' => $category->name,
                        'image' => filled($category->image) ? (string) $category->image : null,
                        'is_motos' => $isMotos,
                        'href' => $isMotos
                            ? route('shop.catalog', ['section' => 'motos'])
                            : route('shop.catalog', [
                                'section' => 'accesorios',
                                'categories' => [$category->id],
                            ]),
                    ];
                })->all();
            },
        );
    }

    /**
     * @return Collection<int, Product>
     */
    private function recommendedProducts(): Collection
    {
        $rankedIds = QueryResultCache::remember(
            'shop.header.recommended_product_ids',
            function (): array {
                return OrderItem::query()
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'products.id', '=', 'order_items.product_id')
                    ->where('products.status', ProductStatus::Active)
                    ->whereNotIn('orders.status', [
                        OrderStatus::Cancelled,
                        OrderStatus::Refunded,
                    ])
                    ->groupBy('order_items.product_id')
                    ->orderByRaw('SUM(order_items.quantity) DESC')
                    ->limit(self::RECOMMENDED_LIMIT)
                    ->pluck('order_items.product_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
            },
        );

        if ($rankedIds === []) {
            return Product::query()
                ->active()
                ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
                ->latest('id')
                ->limit(self::RECOMMENDED_LIMIT)
                ->get()
                ->map(fn (Product $product) => $this->offerPresenter->apply($product));
        }

        return Product::query()
            ->whereIn('id', $rankedIds)
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
            ->get()
            ->sortBy(fn (Product $product): int => array_search($product->id, $rankedIds, true))
            ->values()
            ->map(fn (Product $product) => $this->offerPresenter->apply($product));
    }
}
