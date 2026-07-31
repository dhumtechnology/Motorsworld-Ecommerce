<?php

namespace App\Http\Controllers\Shop;

use App\Enums\Orders\OrderStatus;
use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CatalogIndexRequest;
use App\Models\Orders\OrderItem;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use App\Services\Cart\CartResolver;
use App\Services\Products\ProductOfferPresenter;
use App\Support\QueryResultCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogController extends Controller
{
    private const MOTOS_CATEGORY = 'MOTOS';

    private const PER_PAGE = 12;

    private const FEATURED_LIMIT = 10;

    private const LOG_CHANNEL = 'catalog';

    private ?int $motosCategoryId = null;

    private bool $motosCategoryIdResolved = false;

    public function index(CatalogIndexRequest $request): View|JsonResponse
    {
        $context = $this->requestContext($request);

        try {
            $section = $request->section();
            $motosCategoryId = $this->motosCategoryId();

            if ($motosCategoryId === null) {
                Log::channel(self::LOG_CHANNEL)->warning('MOTOS category missing in database', [
                    'expected_name' => self::MOTOS_CATEGORY,
                    'section' => $section,
                ]);
            }

            $products = $this->buildCatalogQuery($request, $section)
                ->paginate(self::PER_PAGE)
                ->withQueryString();

            $products->through(fn (Product $product) => $this->withActiveOfferPricing($product));

            $cartQuantities = $this->cartQuantitiesByProduct($request);

            if ($request->boolean('infinite')) {
                return response()->json([
                    'html' => view('shop.catalog._product-cards', [
                        'products' => $products,
                        'cartQuantities' => $cartQuantities,
                    ])->render(),
                    'has_more' => $products->hasMorePages(),
                    'next_page' => $products->hasMorePages() ? $products->currentPage() + 1 : null,
                ]);
            }

            $filterOptions = [
                'categories' => $this->categoryOptions($section),
                'brands' => $this->brandOptions($section),
                'models' => $this->modelOptions($section, $request->brandIds()),
                'price' => $this->priceBounds($section),
            ];

            $popularProducts = $this->popularNonMotoProducts();

            $priceBounds = $filterOptions['price'];

            return view('shop.catalog.index', [
                'products' => $products,
                'popularProducts' => $popularProducts,
                'cartQuantities' => $cartQuantities,
                'section' => $section,
                'filters' => [
                    'categories' => $request->categoryIds(),
                    'brands' => $request->brandIds(),
                    'models' => $request->modelIds(),
                    'search' => $request->searchTerm(),
                    'price_min' => $request->priceMin() ?? $priceBounds['min'],
                    'price_max' => $request->priceMax() ?? $priceBounds['max'],
                ],
                'filterOptions' => $filterOptions,
            ]);
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->error('Catalog request failed', [
                ...$context,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<int, int> product_id => quantity in cart
     */
    private function cartQuantitiesByProduct(CatalogIndexRequest $request): array
    {
        $cart = app(CartResolver::class)->resolve(
            $request->user(),
            $request->session()->getId(),
        );

        return $cart->items()
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function requestContext(CatalogIndexRequest $request): array
    {
        return [
            'section' => $request->section(),
            'category_ids' => $request->categoryIds(),
            'brand_ids' => $request->brandIds(),
            'model_ids' => $request->modelIds(),
            'price_min' => $request->priceMin(),
            'price_max' => $request->priceMax(),
            'search' => $request->searchTerm(),
            'page' => (int) $request->input('page', 1),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ];
    }

    private function withActiveOfferPricing(Product $product): Product
    {
        return app(ProductOfferPresenter::class)->apply($product);
    }

    /**
     * Top productos por ventas excluyendo MOTOS (carrusel del catálogo).
     *
     * @return Collection<int, Product>
     */
    private function popularNonMotoProducts(): Collection
    {
        $products = $this->featuredProducts('accesorios');

        if ($products->isNotEmpty()) {
            return $products;
        }

        $motosCategoryId = $this->motosCategoryId();

        return Product::query()
            ->active()
            ->when(
                $motosCategoryId !== null,
                fn (Builder $q) => $q->where('category_id', '!=', $motosCategoryId),
            )
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
            ->latest('id')
            ->limit(self::FEATURED_LIMIT)
            ->get()
            ->map(fn (Product $product) => $this->withActiveOfferPricing($product));
    }

    /**
     * Top productos por unidades vendidas (pedidos no cancelados ni reembolsados).
     *
     * @return Collection<int, Product>
     */
    private function featuredProducts(string $section): Collection
    {
        $motosCategoryId = $this->motosCategoryId();

        $rankedIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.status', ProductStatus::Active)
            ->whereNotIn('orders.status', [
                OrderStatus::Cancelled,
                OrderStatus::Refunded,
            ])
            ->when(
                $section === 'motos',
                fn (Builder $q) => $motosCategoryId
                    ? $q->where('products.category_id', $motosCategoryId)
                    : $q->whereRaw('0 = 1'),
                fn (Builder $q) => $motosCategoryId
                    ? $q->where('products.category_id', '!=', $motosCategoryId)
                    : $q,
            )
            ->groupBy('order_items.product_id')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(self::FEATURED_LIMIT)
            ->pluck('order_items.product_id');

        if ($rankedIds->isEmpty()) {
            return collect();
        }

        $orderById = $rankedIds->values()->all();

        return Product::query()
            ->whereIn('id', $orderById)
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
            ->get()
            ->sortBy(fn (Product $product): int => array_search($product->id, $orderById, true))
            ->values()
            ->map(fn (Product $product) => $this->withActiveOfferPricing($product));
    }

    /**
     * @return Builder<Product>
     */
    private function buildCatalogQuery(CatalogIndexRequest $request, string $section): Builder
    {
        $motosCategoryId = $this->motosCategoryId();

        $query = Product::query()
            ->active()
            ->catalogOrder()
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage']);

        $this->applySectionFilter($query, $section, $motosCategoryId);
        $this->applyCatalogFilters($request, $query, $section, $motosCategoryId);

        return $query;
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySectionFilter(Builder $query, string $section, ?int $motosCategoryId): void
    {
        if ($section === 'motos') {
            if ($motosCategoryId !== null) {
                $query->where('category_id', $motosCategoryId);
            } else {
                $query->whereRaw('0 = 1');
            }

            return;
        }

        if ($motosCategoryId !== null) {
            $query->where('category_id', '!=', $motosCategoryId);
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyCatalogFilters(
        CatalogIndexRequest $request,
        Builder $query,
        string $section,
        ?int $motosCategoryId,
    ): void {
        $categoryIds = $request->categoryIds();

        if ($categoryIds !== []) {
            if ($section === 'accesorios' && $motosCategoryId !== null) {
                $categoryIds = array_values(array_filter(
                    $categoryIds,
                    static fn (int $id): bool => $id !== $motosCategoryId,
                ));

                if ($categoryIds === []) {
                    $query->whereRaw('0 = 1');

                    return;
                }
            }

            if ($section === 'motos' && $motosCategoryId !== null) {
                $categoryIds = [$motosCategoryId];
            }

            $query->whereIn('category_id', $categoryIds);
        }

        if ($brandIds = $request->brandIds()) {
            $query->whereHas(
                'vehicleModel',
                fn (Builder $modelQuery) => $modelQuery->whereIn('brand_id', $brandIds),
            );
        }

        if ($modelIds = $request->modelIds()) {
            $query->whereIn('model_id', $modelIds);
        }

        $priceMin = $request->priceMin();
        $priceMax = $request->priceMax();

        if ($priceMin !== null) {
            $query->where('price_amount', '>=', $priceMin);
        }

        if ($priceMax !== null) {
            $query->where('price_amount', '<=', $priceMax);
        }

        if ($search = $request->searchTerm()) {
            $like = '%'.$search.'%';

            $query->where(function (Builder $searchQuery) use ($like) {
                $searchQuery
                    ->where('sku', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', fn (Builder $q) => $q->where('name', 'like', $like))
                    ->orWhereHas('vehicleModel', function (Builder $q) use ($like) {
                        $q->where('name', 'like', $like)
                            ->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', $like));
                    });
            });
        }
    }

    private function motosCategoryId(): ?int
    {
        if ($this->motosCategoryIdResolved) {
            return $this->motosCategoryId;
        }

        $this->motosCategoryIdResolved = true;
        $this->motosCategoryId = QueryResultCache::remember(
            'catalog.motos_category_id',
            fn (): ?int => $this->resolveMotosCategoryId(),
        );

        return $this->motosCategoryId;
    }

    private function resolveMotosCategoryId(): ?int
    {
        return Category::query()
            ->whereRaw('UPPER(name) = ?', [self::MOTOS_CATEGORY])
            ->value('id');
    }

    /**
     * @return array{min: float, max: float}
     */
    private function priceBounds(string $section): array
    {
        return QueryResultCache::remember(
            "catalog.filter_options.price.{$section}",
            function () use ($section): array {
                $query = Product::query()->active();
                $this->applySectionFilter($query, $section, $this->motosCategoryId());

                $row = $query
                    ->toBase()
                    ->selectRaw('MIN(price_amount) as min_price, MAX(price_amount) as max_price')
                    ->first();

                $minValue = $row?->min_price !== null ? (float) $row->min_price : 0.0;
                $maxValue = $row?->max_price !== null ? (float) $row->max_price : 0.0;

                if ($maxValue < $minValue) {
                    $maxValue = $minValue;
                }

                if ($maxValue === $minValue) {
                    $maxValue = $minValue + 1;
                }

                return [
                    'min' => (float) floor($minValue),
                    'max' => (float) ceil($maxValue),
                ];
            },
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function categoryOptions(string $section)
    {
        return QueryResultCache::rememberRows(
            "catalog.filter_options.categories.{$section}",
            function () use ($section) {
                $motosCategoryId = $this->motosCategoryId();

                return Category::query()
                    ->whereHas('products', fn (Builder $q) => $q->where('status', ProductStatus::Active))
                    ->when(
                        $section === 'motos',
                        fn (Builder $q) => $motosCategoryId
                            ? $q->where('id', $motosCategoryId)
                            : $q->whereRaw('0 = 1'),
                        fn (Builder $q) => $motosCategoryId
                            ? $q->where('id', '!=', $motosCategoryId)
                            : $q,
                    )
                    ->orderBy('name')
                    ->get(['id', 'name']);
            },
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function brandOptions(string $section)
    {
        return QueryResultCache::rememberRows(
            "catalog.filter_options.brands.{$section}",
            fn () => Brand::query()
                ->whereHas('vehicleModels.products', function (Builder $q) use ($section) {
                    $q->where('status', ProductStatus::Active);
                    $this->applySectionFilterOnProductQuery($q, $section);
                })
                ->orderBy('name')
                ->get(['id', 'name']),
        );
    }

    /**
     * @param  list<int>  $brandIds
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function modelOptions(string $section, array $brandIds)
    {
        $brandKey = $brandIds === [] ? 'all' : implode(',', $brandIds);

        return QueryResultCache::rememberRows(
            "catalog.filter_options.models.{$section}.{$brandKey}",
            fn () => VehicleModel::query()
                ->when(
                    $brandIds !== [],
                    fn (Builder $q) => $q->whereIn('brand_id', $brandIds),
                )
                ->whereHas('products', function (Builder $q) use ($section) {
                    $q->where('status', ProductStatus::Active);
                    $this->applySectionFilterOnProductQuery($q, $section);
                })
                ->with('brand:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'brand_id']),
        );
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySectionFilterOnProductQuery(Builder $query, string $section): void
    {
        $motosCategoryId = $this->motosCategoryId();

        if ($section === 'motos' && $motosCategoryId !== null) {
            $query->where('category_id', $motosCategoryId);

            return;
        }

        if ($section === 'accesorios' && $motosCategoryId !== null) {
            $query->where('category_id', '!=', $motosCategoryId);
        }
    }
}
