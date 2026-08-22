<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Actions\Admin\Products\ForceDeleteProductsAction;
use App\Actions\Admin\Products\GenerateProductSkuAction;
use App\Actions\Admin\Products\GetProductDetailsAction;
use App\Actions\Admin\Products\RestoreProductsAction;
use App\Actions\Admin\Products\UpsertProductAction;
use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteProductsRequest;
use App\Http\Requests\Admin\BulkTrashedProductsRequest;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertProductAction $upsertProduct,
        private readonly DeleteProductsAction $deleteProducts,
        private readonly RestoreProductsAction $restoreProducts,
        private readonly ForceDeleteProductsAction $forceDeleteProducts,
        private readonly GenerateProductSkuAction $generateProductSku,
        private readonly GetProductDetailsAction $getProductDetails,
    ) {}

    public function index(ProductIndexRequest $request): View
    {
        return view('admin.products.index', $this->listingViewData($request, trashed: false));
    }

    public function trash(ProductIndexRequest $request): View
    {
        return view('admin.products.trash', $this->listingViewData($request, trashed: true));
    }

    public function create(): View
    {
        return view('admin.products.create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $attributes = $request->productAttributes();
        $attributes['sku'] = ($this->generateProductSku)($attributes['sku'] ?: null);

        $product = $this->upsertProduct->execute(
            $attributes,
            null,
            $request->technicalSheet(),
            false,
            $request->variantsPayload(),
            [],
            $request->defaultGalleryPayload(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Producto «{$product->name}» creado correctamente.");
    }

    public function show(Product $product): View
    {
        return view('admin.products.show', $this->getProductDetails->execute($product));
    }

    public function edit(Product $product): View
    {
        $product->load([
            'category',
            'vehicleModel.brand',
            'images',
            'variants.colors',
            'variants.inventory',
            'variants.images',
        ]);

        return view('admin.products.edit', [
            ...$this->formData(),
            'product' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product = $this->upsertProduct->execute(
            $request->productAttributes(),
            $product,
            $request->technicalSheet(),
            $request->shouldRemoveTechnicalSheet(),
            $request->variantsPayload(),
            $request->removeVariantIds(),
            $request->defaultGalleryPayload(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Producto «{$product->name}» actualizado correctamente.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $result = $this->deleteProducts->execute([$product->id]);

        $message = $result['deleted'] === 1
            ? 'Producto archivado correctamente. Ya no aparecerá en la tienda, pero se conserva el historial de pedidos.'
            : 'No se pudo archivar el producto.';

        return redirect()
            ->route('admin.products.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteProductsRequest $request): RedirectResponse
    {
        $result = $this->deleteProducts->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se archivó ningún producto.',
            $result['deleted'] === 1 => '1 producto archivado correctamente.',
            default => "{$result['deleted']} productos archivados correctamente.",
        };

        return redirect()
            ->route('admin.products.index')
            ->with('status', $message);
    }

    public function restore(int $productId): RedirectResponse
    {
        try {
            $result = $this->restoreProducts->execute([$productId]);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $message = $result['restored'] === 1
            ? 'Producto restaurado correctamente.'
            : 'No se pudo restaurar el producto.';

        return redirect()
            ->route('admin.products.trash')
            ->with('status', $message);
    }

    public function bulkRestore(BulkTrashedProductsRequest $request): RedirectResponse
    {
        try {
            $result = $this->restoreProducts->execute($request->ids());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $message = match (true) {
            $result['restored'] === 0 => 'No se restauró ningún producto.',
            $result['restored'] === 1 => '1 producto restaurado correctamente.',
            default => "{$result['restored']} productos restaurados correctamente.",
        };

        return redirect()
            ->route('admin.products.trash')
            ->with('status', $message);
    }

    public function forceDestroy(int $productId): RedirectResponse
    {
        try {
            $result = $this->forceDeleteProducts->execute([$productId]);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $message = $result['deleted'] === 1
            ? 'Producto eliminado permanentemente.'
            : 'No se pudo eliminar el producto.';

        if ($result['blocked'] !== []) {
            $message .= ' Vinculado a pedidos: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.products.trash')
            ->with('status', $message);
    }

    public function bulkForceDestroy(BulkTrashedProductsRequest $request): RedirectResponse
    {
        try {
            $result = $this->forceDeleteProducts->execute($request->ids());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún producto permanentemente.',
            $result['deleted'] === 1 => '1 producto eliminado permanentemente.',
            default => "{$result['deleted']} productos eliminados permanentemente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (tienen pedidos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.products.trash')
            ->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function listingViewData(ProductIndexRequest $request, bool $trashed): array
    {
        $products = $this->buildListingQuery($request, $trashed)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'models' => VehicleModel::query()->orderBy('name')->get(['id', 'name', 'brand_id']),
            'statuses' => ProductStatus::cases(),
            'filters' => [
                'search' => $request->searchTerm(),
                'categories' => $request->categoryIds(),
                'brands' => $request->brandIds(),
                'models' => $request->modelIds(),
                'status' => $request->status()?->value,
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
            'trashedCount' => Product::query()->onlyTrashed()->count(),
        ];
    }

    /**
     * @return Builder<Product>
     */
    private function buildListingQuery(ProductIndexRequest $request, bool $trashed): Builder
    {
        $query = $trashed ? Product::query()->onlyTrashed() : Product::query();

        return $query
            ->with(['category', 'inventories', 'primaryImage', 'vehicleModel.brand', 'variants.colors'])
            ->when(
                $request->categoryIds() !== [],
                fn (Builder $builder) => $builder->whereIn('category_id', $request->categoryIds()),
            )
            ->when(
                $request->brandIds() !== [],
                fn (Builder $builder) => $builder->whereHas(
                    'vehicleModel',
                    fn (Builder $modelQuery) => $modelQuery->whereIn('brand_id', $request->brandIds()),
                ),
            )
            ->when(
                $request->modelIds() !== [],
                fn (Builder $builder) => $builder->whereIn('model_id', $request->modelIds()),
            )
            ->when(
                $request->status(),
                fn (Builder $builder, ProductStatus $status) => $builder->where('status', $status),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $builder, string $search) {
                    $like = '%'.$search.'%';

                    $builder->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('sku', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhereHas('variants', fn (Builder $q) => $q->where('sku', 'like', $like)->orWhere('name', 'like', $like))
                            ->orWhereHas('category', fn (Builder $q) => $q->where('name', 'like', $like))
                            ->orWhereHas('vehicleModel', function (Builder $q) use ($like) {
                                $q->where('name', 'like', $like)
                                    ->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', $like));
                            });
                    });
                },
            )
            ->when(
                $trashed,
                fn (Builder $builder) => $builder->orderByDesc('deleted_at'),
                fn (Builder $builder) => $builder->orderByDesc('id'),
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'models' => VehicleModel::query()
                ->with('brand:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'brand_id']),
            'statuses' => ProductStatus::cases(),
            'colors' => \App\Models\Products\Color::query()->orderBy('name')->get(['id', 'name', 'hex']),
        ];
    }
}
