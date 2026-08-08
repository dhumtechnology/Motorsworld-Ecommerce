<?php

namespace App\Actions\Admin\Dashboard;

use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Appointments\Appointment;
use App\Models\Auth\User;
use App\Models\Finance\ExchangeRate;
use App\Models\Orders\Order;
use App\Models\Orders\Payment;
use App\Models\Products\Inventory;
use App\Models\Products\Product;
use App\Support\Currency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetDashboardMetricsAction
{
    private const LOW_STOCK_THRESHOLD = 5;

    private const REVENUE_MONTHS = 6;

    /**
     * @return array{
     *     kpis: array<string, mixed>,
     *     exchangeRate: array<string, mixed>|null,
     *     revenueChart: array{labels: list<string>, valuesPen: list<float>, valuesUsd: list<float>},
     *     appointmentStatusChart: array{labels: list<string>, values: list<int>, colors: list<string>},
     *     orderStatusChart: array{labels: list<string>, values: list<int>, colors: list<string>},
     *     upcomingAppointments: Collection,
     *     recentOrders: Collection,
     *     lowStockProducts: Collection
     * }
     */
    public function __invoke(): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $prevMonthStart = $now->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $revenueThisMonthPen = $this->paidRevenueBetween($monthStart, $now, 'PEN');
        $revenueThisMonthUsd = $this->paidRevenueBetween($monthStart, $now, 'USD');
        $revenuePrevMonthPen = $this->paidRevenueBetween($prevMonthStart, $prevMonthEnd, 'PEN');

        $revenueDeltaPercent = $revenuePrevMonthPen > 0
            ? round((($revenueThisMonthPen - $revenuePrevMonthPen) / $revenuePrevMonthPen) * 100, 1)
            : ($revenueThisMonthPen > 0 ? 100.0 : 0.0);

        $latestRate = ExchangeRate::latestAvailable();

        return [
            'kpis' => [
                'customers' => User::query()
                    ->whereHas('roles', fn (Builder $query) => $query->where('name', 'Usuario'))
                    ->count(),
                'pendingAppointments' => Appointment::query()
                    ->where('status', AppointmentStatus::Pending)
                    ->count(),
                'revenueThisMonth' => $revenueThisMonthPen,
                'revenueThisMonthPen' => $revenueThisMonthPen,
                'revenueThisMonthUsd' => $revenueThisMonthUsd,
                'revenuePrevMonth' => $revenuePrevMonthPen,
                'revenueDeltaPercent' => $revenueDeltaPercent,
                'ordersThisMonth' => Order::query()
                    ->where('created_at', '>=', $monthStart)
                    ->count(),
                'ordersToFulfill' => Order::query()
                    ->where('status', OrderStatus::Paid)
                    ->count(),
                'activeProducts' => Product::query()
                    ->where('status', ProductStatus::Active)
                    ->count(),
                'lowStockCount' => Inventory::query()
                    ->where('available_stock', '<=', self::LOW_STOCK_THRESHOLD)
                    ->count(),
                'appointmentsToday' => Appointment::query()
                    ->whereDate('appointment_at', $now->toDateString())
                    ->whereNotIn('status', [
                        AppointmentStatus::Cancelled,
                        AppointmentStatus::Absent,
                    ])
                    ->count(),
            ],
            'exchangeRate' => $latestRate ? [
                'buy' => (float) $latestRate->buy_price,
                'sell' => (float) $latestRate->sell_price,
                'date' => $latestRate->rate_date?->toDateString(),
                'fetched_at' => $latestRate->fetched_at?->timezone('America/Lima')->format('d/m/Y H:i'),
            ] : null,
            'revenueChart' => $this->revenueChart($now),
            'appointmentStatusChart' => $this->appointmentStatusChart(),
            'orderStatusChart' => $this->orderStatusChart(),
            'upcomingAppointments' => Appointment::query()
                ->with(['user.customerProfile', 'serviceType'])
                ->where('appointment_at', '>=', $now)
                ->whereIn('status', [
                    AppointmentStatus::Pending,
                    AppointmentStatus::Accepted,
                    AppointmentStatus::InProgress,
                ])
                ->orderBy('appointment_at')
                ->limit(6)
                ->get(),
            'recentOrders' => Order::query()
                ->with(['user.customerProfile'])
                ->latest('id')
                ->limit(6)
                ->get(),
            'lowStockProducts' => Inventory::query()
                ->with(['product', 'variant'])
                ->where('available_stock', '<=', self::LOW_STOCK_THRESHOLD)
                ->orderBy('available_stock')
                ->limit(6)
                ->get(),
        ];
    }

    private function paidRevenueBetween(Carbon $from, Carbon $to, string $displayCurrency): float
    {
        $payments = Payment::query()
            ->with(['order:id,currency,exchange_rate_sell,total_amount'])
            ->where('status', PaymentRecordStatus::Paid)
            ->whereBetween('paid_at', [$from, $to])
            ->get();

        if ($payments->isNotEmpty()) {
            return round($payments->sum(function (Payment $payment) use ($displayCurrency): float {
                $order = $payment->order;
                $amount = ((int) $payment->amount_cents) / 100;
                $fromCurrency = $payment->currency ?: ($order?->currency ?? 'PEN');
                $sellRate = $order?->exchange_rate_sell !== null
                    ? (float) $order->exchange_rate_sell
                    : null;

                return Currency::convert($amount, $fromCurrency, $displayCurrency, $sellRate);
            }), 2);
        }

        // Fallback: órdenes marcadas como pagadas (seeders / datos sin payments).
        return round((float) Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('updated_at', [$from, $to])
            ->get(['total_amount', 'currency', 'exchange_rate_sell'])
            ->sum(fn (Order $order) => $order->amountIn($displayCurrency)), 2);
    }

    /**
     * @return array{labels: list<string>, valuesPen: list<float>, valuesUsd: list<float>}
     */
    private function revenueChart(Carbon $now): array
    {
        $start = $now->copy()->subMonths(self::REVENUE_MONTHS - 1)->startOfMonth();

        $payments = Payment::query()
            ->with(['order:id,currency,exchange_rate_sell'])
            ->where('status', PaymentRecordStatus::Paid)
            ->where('paid_at', '>=', $start)
            ->get();

        $byMonthPen = [];
        $byMonthUsd = [];

        if ($payments->isNotEmpty()) {
            foreach ($payments as $payment) {
                $key = optional($payment->paid_at)->format('Y-m');
                if ($key === null) {
                    continue;
                }

                $order = $payment->order;
                $amount = ((int) $payment->amount_cents) / 100;
                $fromCurrency = $payment->currency ?: ($order?->currency ?? 'PEN');
                $sellRate = $order?->exchange_rate_sell !== null
                    ? (float) $order->exchange_rate_sell
                    : null;

                $byMonthPen[$key] = ($byMonthPen[$key] ?? 0) + Currency::convert($amount, $fromCurrency, 'PEN', $sellRate);
                $byMonthUsd[$key] = ($byMonthUsd[$key] ?? 0) + Currency::convert($amount, $fromCurrency, 'USD', $sellRate);
            }
        } else {
            $orders = Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->where('updated_at', '>=', $start)
                ->get(['updated_at', 'total_amount', 'currency', 'exchange_rate_sell']);

            foreach ($orders as $order) {
                $key = optional($order->updated_at)->format('Y-m');
                if ($key === null) {
                    continue;
                }

                $byMonthPen[$key] = ($byMonthPen[$key] ?? 0) + $order->amountIn('PEN');
                $byMonthUsd[$key] = ($byMonthUsd[$key] ?? 0) + $order->amountIn('USD');
            }
        }

        $labels = [];
        $valuesPen = [];
        $valuesUsd = [];

        for ($i = self::REVENUE_MONTHS - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $labels[] = $month->locale('es')->translatedFormat('M Y');
            $valuesPen[] = round((float) ($byMonthPen[$key] ?? 0), 2);
            $valuesUsd[] = round((float) ($byMonthUsd[$key] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'valuesPen' => $valuesPen,
            'valuesUsd' => $valuesUsd,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    private function appointmentStatusChart(): array
    {
        $counts = Appointment::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($row) {
                $key = $row->status instanceof AppointmentStatus
                    ? $row->status->value
                    : (string) $row->status;

                return [$key => (int) $row->aggregate];
            });

        $meta = [
            AppointmentStatus::Pending->value => ['label' => 'Pendiente', 'color' => '#f59e0b'],
            AppointmentStatus::Accepted->value => ['label' => 'Aceptada', 'color' => '#10b981'],
            AppointmentStatus::InProgress->value => ['label' => 'En curso', 'color' => '#0ea5e9'],
            AppointmentStatus::Attended->value => ['label' => 'Atendida', 'color' => '#059669'],
            AppointmentStatus::Absent->value => ['label' => 'Ausente', 'color' => '#a3a3a3'],
            AppointmentStatus::Cancelled->value => ['label' => 'Cancelada', 'color' => '#ef4444'],
        ];

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($meta as $status => $item) {
            $labels[] = $item['label'];
            $values[] = (int) ($counts[$status] ?? 0);
            $colors[] = $item['color'];
        }

        return compact('labels', 'values', 'colors');
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    private function orderStatusChart(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($row) {
                $key = $row->status instanceof OrderStatus
                    ? $row->status->value
                    : (string) $row->status;

                return [$key => (int) $row->aggregate];
            });

        $meta = [
            OrderStatus::Created->value => ['label' => 'Creada', 'color' => '#a3a3a3'],
            OrderStatus::Paid->value => ['label' => 'Pagada', 'color' => '#0ea5e9'],
            OrderStatus::Processing->value => ['label' => 'En proceso', 'color' => '#f59e0b'],
            OrderStatus::Shipped->value => ['label' => 'Enviada', 'color' => '#6366f1'],
            OrderStatus::Delivered->value => ['label' => 'Entregada', 'color' => '#10b981'],
            OrderStatus::Cancelled->value => ['label' => 'Cancelada', 'color' => '#ef4444'],
            OrderStatus::Refunded->value => ['label' => 'Reembolsada', 'color' => '#ff6600'],
        ];

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($meta as $status => $item) {
            $count = (int) ($counts[$status] ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $item['label'];
            $values[] = $count;
            $colors[] = $item['color'];
        }

        return compact('labels', 'values', 'colors');
    }
}
