<?php

namespace App\Actions\Admin\Dashboard;

use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Appointments\Appointment;
use App\Models\Auth\User;
use App\Models\Orders\Order;
use App\Models\Orders\Payment;
use App\Models\Products\Inventory;
use App\Models\Products\Product;
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
     *     revenueChart: array{labels: list<string>, values: list<float>},
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

        $revenueThisMonth = $this->paidRevenueBetween($monthStart, $now);
        $revenuePrevMonth = $this->paidRevenueBetween($prevMonthStart, $prevMonthEnd);

        $revenueDeltaPercent = $revenuePrevMonth > 0
            ? round((($revenueThisMonth - $revenuePrevMonth) / $revenuePrevMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100.0 : 0.0);

        return [
            'kpis' => [
                'customers' => User::query()
                    ->whereHas('roles', fn (Builder $query) => $query->where('name', 'Usuario'))
                    ->count(),
                'pendingAppointments' => Appointment::query()
                    ->where('status', AppointmentStatus::Pending)
                    ->count(),
                'revenueThisMonth' => $revenueThisMonth,
                'revenuePrevMonth' => $revenuePrevMonth,
                'revenueDeltaPercent' => $revenueDeltaPercent,
                'ordersThisMonth' => Order::query()
                    ->where('created_at', '>=', $monthStart)
                    ->count(),
                'ordersToFulfill' => Order::query()
                    ->whereIn('status', [
                        OrderStatus::Paid,
                        OrderStatus::Processing,
                    ])
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
            'revenueChart' => $this->revenueChart($now),
            'appointmentStatusChart' => $this->appointmentStatusChart(),
            'orderStatusChart' => $this->orderStatusChart(),
            'upcomingAppointments' => Appointment::query()
                ->with(['user.customerProfile', 'serviceType'])
                ->where('appointment_at', '>=', $now)
                ->whereIn('status', [
                    AppointmentStatus::Pending,
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
                ->with('product')
                ->where('available_stock', '<=', self::LOW_STOCK_THRESHOLD)
                ->orderBy('available_stock')
                ->limit(6)
                ->get(),
        ];
    }

    private function paidRevenueBetween(Carbon $from, Carbon $to): float
    {
        $cents = (int) Payment::query()
            ->where('status', PaymentRecordStatus::Paid)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount_cents');

        if ($cents > 0) {
            return round($cents / 100, 2);
        }

        // Fallback: órdenes marcadas como pagadas (seeders / datos sin payments).
        return round((float) Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('updated_at', [$from, $to])
            ->sum('total_amount'), 2);
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function revenueChart(Carbon $now): array
    {
        $start = $now->copy()->subMonths(self::REVENUE_MONTHS - 1)->startOfMonth();

        $rows = Payment::query()
            ->where('status', PaymentRecordStatus::Paid)
            ->where('paid_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount_cents) as total_cents")
            ->groupBy('ym')
            ->pluck('total_cents', 'ym');

        if ($rows->isEmpty()) {
            $rows = Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->where('updated_at', '>=', $start)
                ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as ym, SUM(total_amount) as total_amount")
                ->groupBy('ym')
                ->pluck('total_amount', 'ym')
                ->map(fn ($amount) => (float) $amount * 100);
        }

        $labels = [];
        $values = [];

        for ($i = self::REVENUE_MONTHS - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $labels[] = $month->locale('es')->translatedFormat('M Y');
            $cents = (float) ($rows[$key] ?? 0);
            $values[] = round($cents / 100, 2);
        }

        return [
            'labels' => $labels,
            'values' => $values,
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
            AppointmentStatus::InProgress->value => ['label' => 'En curso', 'color' => '#0ea5e9'],
            AppointmentStatus::Attended->value => ['label' => 'Atendida', 'color' => '#10b981'],
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
