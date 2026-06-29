<?php

namespace App\Http\Controllers\Shop;

use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CatalogIndexRequest;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use App\Support\QueryResultCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogController extends Controller
{
    private const MOTOS_CATEGORY = 'MOTOS';

    private const PER_PAGE = 12;

    private const LOG_CHANNEL = 'catalog';

    private ?int $motosCategoryId = null;

    private bool $motosCategoryIdResolved = false;

    public function index(CatalogIndexRequest $request): View
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

            $filterOptions = [
                'categories' => $this->categoryOptions($section),
                'brands' => $this->brandOptions($section),
                'models' => $this->modelOptions($section, $request->brandIds()),
            ];

            return view('shop.catalog.index', [
                'products' => $products,
                'section' => $section,
                'filters' => [
                    'categories' => $request->categoryIds(),
                    'brands' => $request->brandIds(),
                    'models' => $request->modelIds(),
                    'search' => $request->searchTerm(),
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
     * @return array<string, mixed>
     */
    private function requestContext(CatalogIndexRequest $request): array
    {
        return [
            'section' => $request->section(),
            'category_ids' => $request->categoryIds(),
            'brand_ids' => $request->brandIds(),
            'model_ids' => $request->modelIds(),
            'search' => $request->searchTerm(),
            'page' => (int) $request->input('page', 1),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ];
    }

    private function withActiveOfferPricing(Product $product): Product
    {
        $pricing = $product->currentPricing();

        $product->setAttribute('is_on_sale', $pricing->hasOffer());
        $product->setAttribute('sale_price', $pricing->hasOffer() ? $pricing->unitPrice : null);
        $product->setAttribute('list_price', $pricing->listUnitPrice);
        $product->setAttribute('effective_price', $pricing->unitPrice);

        if ($offer = $product->activeOfferAt()) {
            $product->setAttribute('offer_ends_at', $offer->ends_at);
        }

        return $product;
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
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer']);

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
