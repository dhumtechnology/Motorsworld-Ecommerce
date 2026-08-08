<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Roles\DeleteRolesAction;
use App\Actions\Admin\Roles\UpsertRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteRolesRequest;
use App\Http\Requests\Admin\RoleIndexRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class RoleController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertRoleAction $upsertRole,
        private readonly DeleteRolesAction $deleteRoles,
    ) {}

    public function index(RoleIndexRequest $request): View
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when(
                $request->searchTerm(),
                fn (Builder $query, string $search) => $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                }),
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
            'filters' => [
                'search' => $request->searchTerm(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->upsertRole->execute($request->roleAttributes());

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Rol «{$role->name}» creado correctamente.");
    }

    public function show(Role $role): View
    {
        $role->load(['permissions' => fn ($query) => $query->orderBy('slug')])
            ->loadCount('users');

        return view('admin.roles.show', [
            'role' => $role,
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions:id');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role = $this->upsertRole->execute($request->roleAttributes(), $role);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Rol «{$role->name}» actualizado correctamente.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $result = $this->deleteRoles->execute([$role->id]);

        $message = $result['deleted'] === 1
            ? 'Rol eliminado correctamente.'
            : 'No se pudo eliminar el rol.';

        if ($result['blocked'] !== []) {
            $message .= ' Roles del sistema protegidos: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteRolesRequest $request): RedirectResponse
    {
        $result = $this->deleteRoles->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún rol.',
            $result['deleted'] === 1 => '1 rol eliminado correctamente.',
            default => "{$result['deleted']} roles eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' Roles del sistema protegidos: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('status', $message);
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, Permission>>
     */
    private function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('slug')
            ->get()
            ->groupBy(function (Permission $permission): string {
                $parts = explode('.', $permission->slug, 2);

                return $parts[0] ?: 'otros';
            })
            ->all();
    }
}
