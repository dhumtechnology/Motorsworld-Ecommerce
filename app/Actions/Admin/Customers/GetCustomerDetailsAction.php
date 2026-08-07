<?php

namespace App\Actions\Admin\Customers;

use App\Enums\Orders\OrderStatus;
use App\Models\Auth\User;
use Illuminate\Support\Collection;

class GetCustomerDetailsAction
{
    /**
     * @return array{
     *     customer: User,
     *     stats: array{
     *         orders_count: int,
     *         appointments_count: int,
     *         paid_orders_count: int,
     *         total_spent: float,
     *         last_order_at: mixed,
     *         last_appointment_at: mixed
     *     },
     *     orders: Collection<int, \App\Models\Orders\Order>,
     *     appointments: Collection<int, \App\Models\Appointments\Appointment>
     * }
     */
    public function execute(User $customer): array
    {
        $customer->loadMissing('customerProfile', 'roles');

        $orders = $customer->orders()
            ->with(['items.product:id,name,sku', 'payments'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $appointments = $customer->appointments()
            ->with(['serviceType:id,name', 'vehicleModel.brand:id,name', 'servicePackage:id,name'])
            ->orderByDesc('appointment_at')
            ->limit(50)
            ->get();

        $spendStatuses = [
            OrderStatus::Paid->value,
            OrderStatus::Processing->value,
            OrderStatus::Shipped->value,
            OrderStatus::Delivered->value,
        ];

        $totalSpent = (float) $customer->orders()
            ->whereIn('status', $spendStatuses)
            ->sum('total_amount');

        $paidOrdersCount = (int) $customer->orders()
            ->whereIn('status', $spendStatuses)
            ->count();

        return [
            'customer' => $customer,
            'stats' => [
                'orders_count' => (int) $customer->orders()->count(),
                'appointments_count' => (int) $customer->appointments()->count(),
                'paid_orders_count' => $paidOrdersCount,
                'total_spent' => $totalSpent,
                'last_order_at' => $customer->orders()->max('created_at'),
                'last_appointment_at' => $customer->appointments()->max('appointment_at'),
            ],
            'orders' => $orders,
            'appointments' => $appointments,
        ];
    }
}
