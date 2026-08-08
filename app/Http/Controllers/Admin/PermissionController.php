<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Permissions\DeletePermissionsAction;
use App\Actions\Admin\Permissions\UpsertPermissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeletePermissionsRequest;
use App\Http\Requests\Admin\PermissionIndexRequest;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Models\Auth\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class PermissionController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly UpsertPermissionAction $upsertPermission,
        private readonly DeletePermissionsAction $deletePermissions,
    ) {}

    public function index(PermissionIndexRequest $request): View
    {
        $permissions = Permission::query()
            ->withCount('roles')
            ->when(
                $request->searchTerm(),
                fn (Builder $query, string $search) => $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                }),
            )
            ->orderBy('slug')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'filters' => [
                'search' => $request->searchTerm(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.permissions.create');
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $permission = $this->upsertPermission->execute($request->permissionAttributes());

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', "Permiso «{$permission->name}» creado correctamente.");
    }

    public function show(Permission $permission): View
    {
        $permission->load(['roles' => fn ($query) => $query->orderBy('name')]);

        return view('admin.permissions.show', [
            'permission' => $permission,
        ]);
    }

    public function edit(Permission $permission): View
    {
        return view('admin.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $permission = $this->upsertPermission->execute(
            $request->permissionAttributes(),
            $permission,
        );

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', "Permiso «{$permission->name}» actualizado correctamente.");
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->deletePermissions->execute([$permission->id]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permiso eliminado correctamente.');
    }

    public function bulkDestroy(BulkDeletePermissionsRequest $request): RedirectResponse
    {
        $result = $this->deletePermissions->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún permiso.',
            $result['deleted'] === 1 => '1 permiso eliminado correctamente.',
            default => "{$result['deleted']} permisos eliminados correctamente.",
        };

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', $message);
    }
}
