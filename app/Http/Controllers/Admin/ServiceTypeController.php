<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ServiceTypes\DeleteServiceTypesAction;
use App\Actions\Admin\ServiceTypes\UpsertServiceTypeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteServiceTypesRequest;
use App\Http\Requests\Admin\ServiceTypeIndexRequest;
use App\Http\Requests\Admin\StoreServiceTypeRequest;
use App\Http\Requests\Admin\UpdateServiceTypeRequest;
use App\Models\Appointments\ServiceType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class ServiceTypeController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertServiceTypeAction $upsertServiceType,
        private readonly DeleteServiceTypesAction $deleteServiceTypes,
    ) {}

    public function index(ServiceTypeIndexRequest $request): View
    {
        $serviceTypes = ServiceType::query()
            ->withCount('appointments')
            ->when(
                $request->searchTerm(),
                fn (Builder $query, string $search) => $query->where('name', 'like', '%'.$search.'%'),
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.service-types.index', [
            'serviceTypes' => $serviceTypes,
            'filters' => [
                'search' => $request->searchTerm(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.service-types.create');
    }

    public function store(StoreServiceTypeRequest $request): RedirectResponse
    {
        $serviceType = $this->upsertServiceType->execute($request->serviceTypeAttributes());

        return redirect()
            ->route('admin.service-types.index')
            ->with('status', "Servicio «{$serviceType->name}» creado correctamente.");
    }

    public function edit(ServiceType $serviceType): View
    {
        $serviceType->loadCount('appointments');

        return view('admin.service-types.edit', [
            'serviceType' => $serviceType,
        ]);
    }

    public function update(UpdateServiceTypeRequest $request, ServiceType $serviceType): RedirectResponse
    {
        $serviceType = $this->upsertServiceType->execute(
            $request->serviceTypeAttributes(),
            $serviceType,
        );

        return redirect()
            ->route('admin.service-types.index')
            ->with('status', "Servicio «{$serviceType->name}» actualizado correctamente.");
    }

    public function destroy(ServiceType $serviceType): RedirectResponse
    {
        $result = $this->deleteServiceTypes->execute([$serviceType->id]);

        $message = $result['deleted'] === 1
            ? 'Servicio eliminado correctamente.'
            : 'No se pudo eliminar el servicio.';

        if ($result['blocked'] !== []) {
            $message .= ' Tiene reservas asociadas: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.service-types.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteServiceTypesRequest $request): RedirectResponse
    {
        $result = $this->deleteServiceTypes->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún servicio.',
            $result['deleted'] === 1 => '1 servicio eliminado correctamente.',
            default => "{$result['deleted']} servicios eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (tienen reservas): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.service-types.index')
            ->with('status', $message);
    }
}
