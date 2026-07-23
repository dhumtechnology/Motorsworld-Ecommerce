<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Users\DeleteAdminUsersAction;
use App\Actions\Admin\Users\UpsertAdminUserAction;
use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserIndexRequest;
use App\Http\Requests\Admin\BulkDeleteAdminUsersRequest;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Auth\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class AdminUserController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertAdminUserAction $upsertAdminUser,
        private readonly DeleteAdminUsersAction $deleteAdminUsers,
    ) {}

    public function index(AdminUserIndexRequest $request): View
    {
        $users = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'Administrador'))
            ->with('roles:id,name')
            ->when(
                $request->status(),
                fn (Builder $query, UserStatus $status) => $query->where('status', $status),
            )
            ->when(
                $request->searchTerm(),
                fn (Builder $query, string $search) => $query->where('email', 'like', '%'.$search.'%'),
            )
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'statuses' => UserStatus::cases(),
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->status()?->value,
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $user = $this->upsertAdminUser->execute($request->adminUserAttributes());

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Usuario «{$user->email}» creado correctamente.");
    }

    public function edit(User $user): View
    {
        $this->ensureAdminUser($user);

        return view('admin.users.edit', [
            'user' => $user->load('roles:id,name'),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureAdminUser($user);

        $user = $this->upsertAdminUser->execute(
            $request->adminUserAttributes(),
            $user,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Usuario «{$user->email}» actualizado correctamente.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureAdminUser($user);

        $result = $this->deleteAdminUsers->execute(
            [$user->id],
            $this->currentUserId(),
        );

        $message = $result['deleted'] === 1
            ? 'Usuario eliminado correctamente.'
            : 'No se pudo eliminar el usuario.';

        if ($result['blocked'] !== []) {
            $message .= ' No puedes eliminar tu propia cuenta.';
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteAdminUsersRequest $request): RedirectResponse
    {
        $result = $this->deleteAdminUsers->execute(
            $request->ids(),
            $this->currentUserId(),
        );

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún usuario.',
            $result['deleted'] === 1 => '1 usuario eliminado correctamente.',
            default => "{$result['deleted']} usuarios eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminó tu propia cuenta: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', $message);
    }

    private function ensureAdminUser(User $user): void
    {
        abort_unless($user->hasRole('Administrador'), 404);
    }

    private function currentUserId(): ?int
    {
        $id = auth()->id();

        return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
    }
}
