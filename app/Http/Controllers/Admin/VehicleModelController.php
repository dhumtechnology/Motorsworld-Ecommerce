<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Models\DeleteVehicleModelsAction;
use App\Actions\Admin\Models\UpsertVehicleModelAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteVehicleModelsRequest;
use App\Http\Requests\Admin\StoreVehicleModelRequest;
use App\Http\Requests\Admin\UpdateVehicleModelRequest;
use App\Http\Requests\Admin\VehicleModelIndexRequest;
use App\Models\Products\Brand;
use App\Models\Products\VehicleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class VehicleModelController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertVehicleModelAction $upsertVehicleModel,
        private readonly DeleteVehicleModelsAction $deleteVehicleModels,
    ) {}

    public function index(VehicleModelIndexRequest $request): View
    {
        $models = VehicleModel::query()
            ->with('brand:id,name')
            ->withCount('products')
            ->when(
                $request->brandIds() !== [],
                fn (Builder $query) => $query->whereIn('brand_id', $request->brandIds()),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('name', 'like', $like)
                            ->orWhereHas('brand', fn (Builder $q) => $q->where('name', 'like', $like));
                    });
                },
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.models.index', [
            'models' => $models,
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->searchTerm(),
                'brands' => $request->brandIds(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.models.create', $this->formData());
    }

    public function store(StoreVehicleModelRequest $request): RedirectResponse
    {
        $model = $this->upsertVehicleModel->execute($request->modelAttributes());

        return redirect()
            ->route('admin.models.index')
            ->with('status', "Modelo «{$model->name}» creado correctamente.");
    }

    public function edit(VehicleModel $vehicleModel): View
    {
        $vehicleModel->load(['brand'])->loadCount('products');

        return view('admin.models.edit', [
            ...$this->formData(),
            'vehicleModel' => $vehicleModel,
        ]);
    }

    public function update(UpdateVehicleModelRequest $request, VehicleModel $vehicleModel): RedirectResponse
    {
        $model = $this->upsertVehicleModel->execute($request->modelAttributes(), $vehicleModel);

        return redirect()
            ->route('admin.models.index')
            ->with('status', "Modelo «{$model->name}» actualizado correctamente.");
    }

    public function destroy(VehicleModel $vehicleModel): RedirectResponse
    {
        $result = $this->deleteVehicleModels->execute([$vehicleModel->id]);

        $message = $result['deleted'] === 1
            ? 'Modelo eliminado correctamente.'
            : 'No se pudo eliminar el modelo.';

        if ($result['blocked'] !== []) {
            $message .= ' Tiene productos asociados: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.models.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteVehicleModelsRequest $request): RedirectResponse
    {
        $result = $this->deleteVehicleModels->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún modelo.',
            $result['deleted'] === 1 => '1 modelo eliminado correctamente.',
            default => "{$result['deleted']} modelos eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (tienen productos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.models.index')
            ->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
