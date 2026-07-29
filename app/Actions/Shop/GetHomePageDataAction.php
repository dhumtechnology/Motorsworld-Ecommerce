<?php

namespace App\Actions\Shop;

use App\Enums\Orders\OrderStatus;
use App\Enums\Products\ProductStatus;
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
     *     needLinks: list<array{key: string, label: string, href: string, image: string}>
     * }
     */
    public function execute(): array
    {
        return [
            'popularProducts' => $this->popularProducts(),
            'needLinks' => $this->needLinks(),
        ];
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
            // Fallback: productos activos no-moto más recientes si aún no hay ventas.
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

    /**
     * @return list<array{key: string, label: string, href: string, image: string}>
     */
    private function needLinks(): array
    {
        $categories = $this->categoryMap();

        $definitions = [
            [
                'key' => 'motos',
                'label' => 'Motos',
                'image' => 'images/home/need-motos.png',
                'href' => route('shop.catalog', ['section' => 'motos']),
            ],
            [
                'key' => 'baterias',
                'label' => 'Baterías',
                'image' => 'images/home/need-baterias.png',
                'names' => ['Baterías', 'Baterias'],
            ],
            [
                'key' => 'accesorios',
                'label' => 'Accesorios',
                'image' => 'images/home/need-accesorios.png',
                'names' => ['Accesorios'],
            ],
            [
                'key' => 'neumaticos',
                'label' => 'Neumáticos',
                'image' => 'images/home/need-neumaticos.png',
                'names' => ['Neumáticos', 'Neumaticos'],
            ],
            [
                'key' => 'repuestos',
                'label' => 'Repuestos generales',
                'image' => 'images/home/need-repuestos.png',
                'names' => ['Repuestos generales', 'Repuestos'],
            ],
        ];

        $links = [];

        foreach ($definitions as $definition) {
            if ($definition['key'] === 'motos') {
                $links[] = [
                    'key' => $definition['key'],
                    'label' => $definition['label'],
                    'href' => $definition['href'],
                    'image' => asset($definition['image']),
                ];

                continue;
            }

            $categoryId = null;
            foreach ($definition['names'] as $name) {
                if (isset($categories[$name])) {
                    $categoryId = $categories[$name];
                    break;
                }
            }

            $links[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'href' => $categoryId
                    ? route('shop.catalog', [
                        'section' => 'accesorios',
                        'categories' => [$categoryId],
                    ])
                    : route('shop.catalog', [
                        'section' => 'accesorios',
                        'search' => $definition['label'],
                    ]),
                'image' => asset($definition['image']),
            ];
        }

        return $links;
    }

    /**
     * @return array<string, int> name => id
     */
    private function categoryMap(): array
    {
        return QueryResultCache::remember(
            'home.category_name_to_id',
            function (): array {
                return Category::query()
                    ->get(['id', 'name'])
                    ->mapWithKeys(fn (Category $category) => [$category->name => $category->id])
                    ->all();
            },
        );
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
