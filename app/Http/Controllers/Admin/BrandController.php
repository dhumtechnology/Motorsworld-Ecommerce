<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Brands\DeleteBrandsAction;
use App\Actions\Admin\Brands\UpsertBrandAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandIndexRequest;
use App\Http\Requests\Admin\BulkDeleteBrandsRequest;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Products\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class BrandController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertBrandAction $upsertBrand,
        private readonly DeleteBrandsAction $deleteBrands,
    ) {}

    public function index(BrandIndexRequest $request): View
    {
        $brands = Brand::query()
            ->withCount(['vehicleModels', 'products'])
            ->when(
                $request->searchTerm(),
                fn (Builder $query, string $search) => $query->where('name', 'like', '%'.$search.'%'),
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.brands.index', [
            'brands' => $brands,
            'filters' => [
                'search' => $request->searchTerm(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $brand = $this->upsertBrand->execute(
            $request->brandAttributes(),
            null,
            $request->imageFile(),
        );

        return redirect()
            ->route('admin.brands.index')
            ->with('status', "Marca «{$brand->name}» creada correctamente.");
    }

    public function edit(Brand $brand): View
    {
        $brand->loadCount('vehicleModels');

        return view('admin.brands.edit', [
            'brand' => $brand,
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand = $this->upsertBrand->execute(
            $request->brandAttributes(),
            $brand,
            $request->imageFile(),
            $request->shouldRemoveImage(),
        );

        return redirect()
            ->route('admin.brands.index')
            ->with('status', "Marca «{$brand->name}» actualizada correctamente.");
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $result = $this->deleteBrands->execute([$brand->id]);

        $message = $result['deleted'] === 1
            ? 'Marca eliminada correctamente.'
            : 'No se pudo eliminar la marca.';

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (productos en pedidos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.brands.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteBrandsRequest $request): RedirectResponse
    {
        $result = $this->deleteBrands->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ninguna marca.',
            $result['deleted'] === 1 => '1 marca eliminada correctamente.',
            default => "{$result['deleted']} marcas eliminadas correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (productos en pedidos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.brands.index')
            ->with('status', $message);
    }
}
