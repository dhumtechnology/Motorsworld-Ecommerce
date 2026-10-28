<?php

namespace App\Actions\Admin\Customers;

use App\Enums\Orders\OrderStatus;
use App\Models\Appointments\Appointment;
use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use App\Models\Coupons\CouponUsage;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Builder;
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
     *     orders: Collection<int, Order>,
     *     appointments: Collection<int, Appointment>,
     *     assignedCoupons: Collection<int, Coupon>,
     *     usedCoupons: Collection<int, CouponUsage>,
     *     assignableCoupons: Collection<int, Coupon>
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

        $assignedCoupons = $customer->assignedCoupons()
            ->withCount('usages')
            ->orderBy('code')
            ->get();

        $usedCoupons = $customer->couponUsages()
            ->with(['coupon:id,code,name', 'order:id,total_amount,currency,created_at'])
            ->orderByDesc('id')
            ->get();

        $assignedIds = $assignedCoupons->pluck('id')->all();
        $usedCouponIds = $usedCoupons->pluck('coupon_id')->all();

        $assignableCoupons = Coupon::query()
            ->activeAt()
            ->when(
                $assignedIds !== [],
                fn (Builder $query) => $query->whereNotIn('id', $assignedIds),
            )
            ->when(
                $usedCouponIds !== [],
                fn (Builder $query) => $query->whereNotIn('id', $usedCouponIds),
            )
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

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
            'assignedCoupons' => $assignedCoupons,
            'usedCoupons' => $usedCoupons,
            'assignableCoupons' => $assignableCoupons,
        ];
    }
}
