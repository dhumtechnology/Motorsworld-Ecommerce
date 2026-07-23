<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\PaymentMethods\DeletePaymentMethodsAction;
use App\Actions\Admin\PaymentMethods\UpsertPaymentMethodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeletePaymentMethodsRequest;
use App\Http\Requests\Admin\PaymentMethodIndexRequest;
use App\Http\Requests\Admin\StorePaymentMethodRequest;
use App\Http\Requests\Admin\UpdatePaymentMethodRequest;
use App\Models\Payments\PaymentMethod;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class PaymentMethodController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertPaymentMethodAction $upsertPaymentMethod,
        private readonly DeletePaymentMethodsAction $deletePaymentMethods,
    ) {}

    public function index(PaymentMethodIndexRequest $request): View
    {
        $paymentMethods = PaymentMethod::query()
            ->withCount('payments')
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';
                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    });
                },
            )
            ->when(
                $request->isActiveFilter() !== null,
                fn (Builder $query) => $query->where('is_active', $request->isActiveFilter()),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.payment-methods.index', [
            'paymentMethods' => $paymentMethods,
            'filters' => [
                'search' => $request->searchTerm(),
                'is_active' => $request->input('is_active'),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.create');
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $paymentMethod = $this->upsertPaymentMethod->execute($request->paymentMethodAttributes());

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('status', "Medio de pago «{$paymentMethod->name}» creado correctamente.");
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        $paymentMethod->loadCount('payments');

        return view('admin.payment-methods.edit', [
            'paymentMethod' => $paymentMethod,
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod = $this->upsertPaymentMethod->execute(
            $request->paymentMethodAttributes(),
            $paymentMethod,
        );

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('status', "Medio de pago «{$paymentMethod->name}» actualizado correctamente.");
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $result = $this->deletePaymentMethods->execute([$paymentMethod->id]);

        $message = $result['deleted'] === 1
            ? 'Medio de pago eliminado correctamente.'
            : 'No se pudo eliminar el medio de pago.';

        if ($result['blocked'] !== []) {
            $message .= ' Tiene pagos asociados: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeletePaymentMethodsRequest $request): RedirectResponse
    {
        $result = $this->deletePaymentMethods->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún medio de pago.',
            $result['deleted'] === 1 => '1 medio de pago eliminado correctamente.',
            default => "{$result['deleted']} medios de pago eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (tienen pagos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('status', $message);
    }
}
