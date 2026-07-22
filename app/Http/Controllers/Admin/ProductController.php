<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Actions\Admin\Products\UpsertProductAction;
use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteProductsRequest;
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

class ProductController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertProductAction $upsertProduct,
        private readonly DeleteProductsAction $deleteProducts,
    ) {}

    public function index(ProductIndexRequest $request): View
    {
        $priceBounds = Product::query()
            ->selectRaw('COALESCE(MIN(price_amount), 0) as min_price, COALESCE(MAX(price_amount), 0) as max_price')
            ->first();

        $boundMin = (float) ($priceBounds->min_price ?? 0);
        $boundMax = (float) ($priceBounds->max_price ?? 0);

        if ($boundMax < $boundMin) {
            $boundMax = $boundMin;
        }

        $priceMin = $request->priceMin() ?? $boundMin;
        $priceMax = $request->priceMax() ?? $boundMax;

        if ($priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        $products = Product::query()
            ->with(['category', 'inventory', 'primaryImage', 'vehicleModel.brand'])
            ->when(
                $request->categoryIds() !== [],
                fn (Builder $query) => $query->whereIn('category_id', $request->categoryIds()),
            )
            ->when(
                $request->brandIds() !== [],
                fn (Builder $query) => $query->whereHas(
                    'vehicleModel',
                    fn (Builder $modelQuery) => $modelQuery->whereIn('brand_id', $request->brandIds()),
                ),
            )
            ->when(
                $request->modelIds() !== [],
                fn (Builder $query) => $query->whereIn('model_id', $request->modelIds()),
            )
            ->when(
                $request->status(),
                fn (Builder $query, ProductStatus $status) => $query->where('status', $status),
            )
            ->when(
                $request->priceMin() !== null || $request->priceMax() !== null,
                function (Builder $query) use ($priceMin, $priceMax, $boundMin, $boundMax) {
                    if (abs($priceMin - $boundMin) > 0.0001 || abs($priceMax - $boundMax) > 0.0001) {
                        $query
                            ->where('price_amount', '>=', $priceMin)
                            ->where('price_amount', '<=', $priceMax);
                    }
                },
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('sku', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhereHas('category', fn (Builder $q) => $q->where('name', 'like', $like))
                            ->orWhereHas('vehicleModel', function (Builder $q) use ($like) {
                                $q->where('name', 'like', $like)
                                    ->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', $like));
                            });
                    });
                },
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'models' => VehicleModel::query()->orderBy('name')->get(['id', 'name', 'brand_id']),
            'statuses' => ProductStatus::cases(),
            'priceBounds' => [
                'min' => $boundMin,
                'max' => $boundMax,
            ],
            'filters' => [
                'search' => $request->searchTerm(),
                'categories' => $request->categoryIds(),
                'brands' => $request->brandIds(),
                'models' => $request->modelIds(),
                'status' => $request->status()?->value,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
            ],
            'hasActiveFilters' => $request->searchTerm() !== null
                || $request->categoryIds() !== []
                || $request->brandIds() !== []
                || $request->modelIds() !== []
                || $request->status() !== null
                || abs($priceMin - $boundMin) > 0.0001
                || abs($priceMax - $boundMax) > 0.0001,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->upsertProduct->execute(
            $request->productAttributes(),
            $request->availableStock(),
            null,
            $request->primaryImage(),
            $request->secondaryImages(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Producto «{$product->name}» creado correctamente.");
    }

    public function edit(Product $product): View
    {
        $product->load(['inventory', 'category', 'vehicleModel.brand', 'images']);

        return view('admin.products.edit', [
            ...$this->formData(),
            'product' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product = $this->upsertProduct->execute(
            $request->productAttributes(),
            $request->availableStock(),
            $product,
            $request->primaryImage(),
            $request->secondaryImages(),
            $request->removeImageIds(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Producto «{$product->name}» actualizado correctamente.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $result = $this->deleteProducts->execute([$product->id]);

        $message = $result['deleted'] === 1
            ? 'Producto eliminado correctamente.'
            : 'No se pudo eliminar el producto.';

        if ($result['blocked'] !== []) {
            $message .= ' Vinculado a pedidos: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.products.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteProductsRequest $request): RedirectResponse
    {
        $result = $this->deleteProducts->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún producto.',
            $result['deleted'] === 1 => '1 producto eliminado correctamente.',
            default => "{$result['deleted']} productos eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (tienen pedidos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.products.index')
            ->with('status', $message);
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
        ];
    }
}
