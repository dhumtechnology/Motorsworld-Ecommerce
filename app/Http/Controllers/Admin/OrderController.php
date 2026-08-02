<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Orders\UpdateOrderStatusAction;
use App\Enums\Orders\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Orders\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    private const PER_PAGE = 15;

    public function index(OrderIndexRequest $request): View
    {
        $orders = Order::query()
            ->with(['user.customerProfile'])
            ->withCount('items')
            ->when(
                $request->status(),
                fn (Builder $query, OrderStatus $status) => $query->where('status', $status),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('id', 'like', $like)
                            ->orWhereHas('user', function (Builder $userQuery) use ($like) {
                                $userQuery
                                    ->where('email', 'like', $like)
                                    ->orWhereHas('customerProfile', function (Builder $profileQuery) use ($like) {
                                        $profileQuery
                                            ->where('first_name', 'like', $like)
                                            ->orWhere('last_name', 'like', $like)
                                            ->orWhere('document', 'like', $like)
                                            ->orWhere('phone', 'like', $like);
                                    });
                            });
                    });
                },
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->status()?->value,
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user.customerProfile',
            'items.product',
            'shippingAddress',
            'billingAddress',
            'statusHistory' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id'),
            'payments' => fn ($query) => $query->orderByDesc('id'),
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        UpdateOrderStatusAction $updateOrderStatus,
    ): RedirectResponse {
        $order = $updateOrderStatus->execute(
            $order,
            $request->orderStatus(),
            $request->note(),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', "Estado de la orden #{$order->id} actualizado correctamente.");
    }
}
