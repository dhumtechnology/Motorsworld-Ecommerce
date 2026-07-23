<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Payments\PaymentMethod as PaymentMethodEnum;
use App\Enums\Payments\PaymentRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentIndexRequest;
use App\Models\Orders\Payment;
use App\Models\Payments\PaymentMethod;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PaymentController extends Controller
{
    private const PER_PAGE = 15;

    public function index(PaymentIndexRequest $request): View
    {
        $payments = Payment::query()
            ->with(['order.user.customerProfile'])
            ->when(
                $request->status(),
                fn (Builder $query, PaymentRecordStatus $status) => $query->where('status', $status),
            )
            ->when(
                $request->method(),
                fn (Builder $query, PaymentMethodEnum $method) => $query->where('method', $method),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like, $search) {
                        $searchQuery
                            ->where('id', 'like', $like)
                            ->orWhere('order_id', 'like', $like)
                            ->orWhere('payment_code', 'like', $like)
                            ->orWhere('culqi_charge_id', 'like', $like)
                            ->orWhereHas('order.user', function (Builder $userQuery) use ($like) {
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

                        if (ctype_digit(ltrim($search, '#'))) {
                            $orderId = (int) ltrim($search, '#');
                            $searchQuery->orWhere('order_id', $orderId)->orWhere('id', $orderId);
                        }
                    });
                },
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'statuses' => PaymentRecordStatus::cases(),
            'methods' => $this->methodOptions(),
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->status()?->value,
                'method' => $request->method()?->value,
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['order.user.customerProfile', 'order.items.product']);

        $methodLabel = $this->resolveMethodLabel($payment);

        return view('admin.payments.show', [
            'payment' => $payment,
            'methodLabel' => $methodLabel,
        ]);
    }

    /**
     * @return Collection<int, object{value: string, label: string}>
     */
    private function methodOptions(): Collection
    {
        $catalog = PaymentMethod::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name']);

        if ($catalog->isNotEmpty()) {
            return $catalog->map(fn (PaymentMethod $method) => (object) [
                'value' => $method->code,
                'label' => $method->name,
            ]);
        }

        return collect(PaymentMethodEnum::cases())->map(fn (PaymentMethodEnum $method) => (object) [
            'value' => $method->value,
            'label' => $method->label(),
        ]);
    }

    private function resolveMethodLabel(Payment $payment): string
    {
        $code = $payment->method instanceof PaymentMethodEnum
            ? $payment->method->value
            : (string) ($payment->method ?? '');

        if ($code === '') {
            return '—';
        }

        $catalogName = PaymentMethod::query()->where('code', $code)->value('name');

        if (is_string($catalogName) && $catalogName !== '') {
            return $catalogName;
        }

        $enum = PaymentMethodEnum::tryFrom($code);

        return $enum?->label() ?? $code;
    }
}
