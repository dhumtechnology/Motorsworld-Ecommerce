<?php

namespace App\Http\Controllers\Shop;

use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CatalogIndexRequest;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CatalogController extends Controller
{
    private const MOTOS_CATEGORY = 'MOTOS';

    private const PER_PAGE = 12;

    private ?int $motosCategoryId = null;

    private bool $motosCategoryIdResolved = false;

    public function index(CatalogIndexRequest $request): View
    {
        $section = $request->section();

        $products = $this->buildCatalogQuery($request, $section)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('shop.catalog.index', [
            'products' => $products,
            'section' => $section,
            'filters' => [
                'category' => $request->categoryId(),
                'brand' => $request->brandId(),
                'model' => $request->modelId(),
                'search' => $request->searchTerm(),
            ],
            'filterOptions' => [
                'categories' => $this->categoryOptions($section),
                'brands' => $this->brandOptions($section),
                'models' => $this->modelOptions($section, $request->brandId()),
            ],
        ]);
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
            ->with(['category', 'vehicleModel.brand', 'inventory']);

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
        if ($categoryId = $request->categoryId()) {
            if ($section === 'accesorios' && $categoryId === $motosCategoryId) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('category_id', $categoryId);
            }
        }

        if ($brandId = $request->brandId()) {
            $query->whereHas('vehicleModel', fn (Builder $modelQuery) => $modelQuery->where('brand_id', $brandId));
        }

        if ($modelId = $request->modelId()) {
            $query->where('model_id', $modelId);
        }

        if ($search = $request->searchTerm()) {
            $like = '%'.$search.'%';

            $query->where(function (Builder $searchQuery) use ($like) {
                $searchQuery
                    ->where('sku', 'like', $like)
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
        $this->motosCategoryId = Category::query()
            ->whereRaw('UPPER(name) = ?', [self::MOTOS_CATEGORY])
            ->value('id');

        return $this->motosCategoryId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    private function categoryOptions(string $section)
    {
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
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Brand>
     */
    private function brandOptions(string $section)
    {
        return Brand::query()
            ->whereHas('vehicleModels.products', function (Builder $q) use ($section) {
                $q->where('status', ProductStatus::Active);
                $this->applySectionFilterOnProductQuery($q, $section);
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, VehicleModel>
     */
    private function modelOptions(string $section, ?int $brandId)
    {
        return VehicleModel::query()
            ->when($brandId, fn (Builder $q) => $q->where('brand_id', $brandId))
            ->whereHas('products', function (Builder $q) use ($section) {
                $q->where('status', ProductStatus::Active);
                $this->applySectionFilterOnProductQuery($q, $section);
            })
            ->with('brand:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'brand_id']);
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
