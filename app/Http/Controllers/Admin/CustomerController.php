<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerIndexRequest;
use App\Models\Auth\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CustomerController extends Controller
{
    private const PER_PAGE = 15;

    public function index(CustomerIndexRequest $request): View
    {
        $customers = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'Usuario'))
            ->with('customerProfile')
            ->withCount('orders')
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
}
