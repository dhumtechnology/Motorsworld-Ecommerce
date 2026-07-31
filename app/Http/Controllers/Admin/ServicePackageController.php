<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ServicePackages\DeleteServicePackagesAction;
use App\Actions\Admin\ServicePackages\UpsertServicePackageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteServicePackagesRequest;
use App\Http\Requests\Admin\ServicePackageIndexRequest;
use App\Http\Requests\Admin\StoreServicePackageRequest;
use App\Http\Requests\Admin\UpdateServicePackageRequest;
use App\Models\Appointments\ServicePackage;
use App\Models\Appointments\ServiceType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class ServicePackageController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertServicePackageAction $upsertServicePackage,
        private readonly DeleteServicePackagesAction $deleteServicePackages,
    ) {}

    public function index(ServicePackageIndexRequest $request): View
    {
        $packages = ServicePackage::query()
            ->with('serviceType')
            ->withCount('appointments')
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';
                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
                },
            )
            ->when(
                $request->serviceTypeId(),
                fn (Builder $query, int $typeId) => $query->where('service_type_id', $typeId),
            )
            ->when(
                $request->isActiveFilter() !== null,
                fn (Builder $query) => $query->where('is_active', $request->isActiveFilter()),
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.service-packages.index', [
            'packages' => $packages,
            'serviceTypes' => ServiceType::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->searchTerm(),
                'service_type_id' => $request->input('service_type_id'),
                'is_active' => $request->input('is_active'),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.service-packages.create', [
            'serviceTypes' => ServiceType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreServicePackageRequest $request): RedirectResponse
    {
        $package = $this->upsertServicePackage->execute($request->servicePackageAttributes());

        return redirect()
            ->route('admin.service-packages.index')
            ->with('status', "Paquete «{$package->name}» creado correctamente.");
    }

    public function edit(ServicePackage $servicePackage): View
    {
        $servicePackage->loadCount('appointments');

        return view('admin.service-packages.edit', [
            'servicePackage' => $servicePackage,
            'serviceTypes' => ServiceType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateServicePackageRequest $request, ServicePackage $servicePackage): RedirectResponse
    {
        $package = $this->upsertServicePackage->execute(
            $request->servicePackageAttributes(),
            $servicePackage,
        );

        return redirect()
            ->route('admin.service-packages.index')
            ->with('status', "Paquete «{$package->name}» actualizado correctamente.");
    }

    public function destroy(ServicePackage $servicePackage): RedirectResponse
    {
        $result = $this->deleteServicePackages->execute([$servicePackage->id]);

        $message = $result['deleted'] === 1
            ? 'Paquete eliminado correctamente.'
            : 'No se pudo eliminar el paquete.';

        if ($result['blocked'] !== []) {
            $message .= ' Tiene reservas asociadas: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.service-packages.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteServicePackagesRequest $request): RedirectResponse
    {
        $result = $this->deleteServicePackages->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún paquete.',
            $result['deleted'] === 1 => '1 paquete eliminado correctamente.',
            default => "{$result['deleted']} paquetes eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (tienen reservas): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.service-packages.index')
            ->with('status', $message);
    }
}
