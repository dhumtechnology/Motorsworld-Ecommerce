<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Customers\DeleteCustomersAction;
use App\Actions\Admin\Customers\GetCustomerDetailsAction;
use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerIndexRequest;
use App\Models\Auth\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly GetCustomerDetailsAction $getCustomerDetails,
        private readonly DeleteCustomersAction $deleteCustomers,
    ) {}

    public function index(CustomerIndexRequest $request): View
    {
        $customers = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'Usuario'))
            ->with('customerProfile')
            ->withCount(['orders', 'appointments'])
            ->when(
                $request->status(),
                fn (Builder $query, UserStatus $status) => $query->where('status', $status),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('email', 'like', $like)
                            ->orWhereHas('customerProfile', function (Builder $profileQuery) use ($like) {
                                $profileQuery
                                    ->where('first_name', 'like', $like)
                                    ->orWhere('last_name', 'like', $like)
                                    ->orWhere('document', 'like', $like)
                                    ->orWhere('phone', 'like', $like);
                            });
                    });
                },
            )
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'statuses' => UserStatus::cases(),
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->status()?->value,
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function show(User $user): View
    {
        $this->ensureCustomer($user);

        return view('admin.customers.show', $this->getCustomerDetails->execute($user));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureCustomer($user);

        $result = $this->deleteCustomers->execute([$user->id]);

        $message = $result['deleted'] === 1
            ? 'Cliente eliminado correctamente.'
            : 'No se pudo eliminar el cliente.';

        return redirect()
            ->route('admin.customers.index')
            ->with('status', $message);
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless($user->hasRole('Usuario'), 404);
    }
}
