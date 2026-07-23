<?php

namespace Database\Seeders;

use App\Actions\Inventory\RegisterInventoryMovementAction;
use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentMethod as PaymentMethodEnum;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Auth\User;
use App\Models\Orders\Address;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Orders\OrderStatusHistory;
use App\Models\Orders\Payment;
use App\Models\Products\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OrderSalesSeeder extends Seeder
{
    private const SEED_NOTE = 'Pedido demo (OrderSalesSeeder)';

    /**
     * Genera pedidos con ventas para visualizar productos destacados en el catálogo.
     * Idempotente: elimina pedidos demo previos antes de volver a crearlos.
     */
    public function run(): void
    {
        $this->resetPreviousSeedOrders();

        $customers = $this->customerPool();

        if ($customers->isEmpty()) {
            $this->command?->warn('OrderSalesSeeder: no hay clientes. Ejecute UserSeeder primero.');

            return;
        }

        $productSkus = Product::query()->pluck('id', 'sku');

        foreach ($this->orderDefinitions() as $index => $definition) {
            $customer = $customers[$index % $customers->count()];
            $address = $this->ensureAddress($customer);
            $placedAt = now()->subDays($definition['days_ago']);

            $lines = $this->resolveLines($definition['lines'], $productSkus);

            if ($lines === []) {
                continue;
            }

            $totalAmount = collect($lines)->sum(
                fn (array $line): float => (float) $line['unit_price'] * $line['quantity'],
            );

            $order = Order::query()->create([
                'user_id' => $customer->id,
                'status' => $definition['status'],
                'payment_status' => $definition['payment_status'],
                'total_amount' => $totalAmount,
                'currency' => 'PEN',
                'shipping_address_id' => $address->id,
                'billing_address_id' => $address->id,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            foreach ($lines as $line) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $line['product_id'],
                    'product_offer_id' => $line['product_offer_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'list_unit_price' => $line['list_unit_price'],
                    'currency' => $line['currency'],
                    'created_at' => $placedAt,
                ]);
            }

            $this->recordStatusHistory($order, $definition['status'], $placedAt);
            $this->recordPayment($order, $definition, $placedAt, $index);

            if ($definition['payment_status'] === PaymentStatus::Paid) {
                $this->recordSaleExits($order);
            }
        }
    }

    private function resetPreviousSeedOrders(): void
    {
        $orderIds = OrderStatusHistory::query()
            ->where('note', self::SEED_NOTE)
            ->pluck('order_id');

        if ($orderIds->isNotEmpty()) {
            \App\Models\Products\InventoryMovement::query()
                ->whereIn('order_id', $orderIds)
                ->delete();

            Order::query()->whereIn('id', $orderIds)->delete();
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function customerPool(): Collection
    {
        $customers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Usuario'))
            ->orderBy('id')
            ->limit(5)
            ->get();

        if ($customers->isNotEmpty()) {
            return $customers;
        }

        $fallback = User::query()->where('email', 'test@example.com')->first();

        return $fallback ? collect([$fallback]) : collect();
    }

    private function ensureAddress(User $user): Address
    {
        return Address::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'line1' => 'Av. Javier Prado Este 4200',
                'city' => 'Lima',
                'postal_code' => '15023',
                'country' => 'PE',
            ],
            [],
        );
    }

    /**
     * @param  array<int, array{sku: string, qty: int}>  $lines
     * @param  Collection<string, int>  $productSkus
     * @return list<array<string, mixed>>
     */
    private function resolveLines(array $lines, Collection $productSkus): array
    {
        $resolved = [];

        foreach ($lines as $line) {
            $productId = $productSkus->get($line['sku']);

            if ($productId === null) {
                continue;
            }

            $product = Product::query()->find($productId);

            if ($product === null) {
                continue;
            }

            $pricing = OrderItem::pricingAttributesFor($product, $line['qty']);

            $resolved[] = [
                'product_id' => $pricing['product_id'],
                'product_offer_id' => $pricing['product_offer_id'],
                'quantity' => $pricing['quantity'],
                'unit_price' => $pricing['unit_price'],
                'list_unit_price' => $pricing['list_unit_price'],
                'currency' => $pricing['currency'],
            ];
        }

        return $resolved;
    }

    private function recordStatusHistory(Order $order, OrderStatus $status, Carbon $placedAt): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'status' => $status->value,
            'note' => self::SEED_NOTE,
            'created_at' => $placedAt,
        ]);
    }

    /**
     * @param  array{
     *     status: OrderStatus,
     *     payment_status: PaymentStatus,
     *     days_ago: int,
     *     lines: list<array{sku: string, qty: int}>
     * }  $definition
     */
    private function recordPayment(Order $order, array $definition, Carbon $placedAt, int $index): void
    {
        $methods = PaymentMethodEnum::cases();
        $method = $methods[$index % count($methods)];

        $paymentStatus = match ($definition['payment_status']) {
            PaymentStatus::Paid => PaymentRecordStatus::Paid,
            PaymentStatus::Failed => PaymentRecordStatus::Failed,
            PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded => PaymentRecordStatus::Refunded,
            default => PaymentRecordStatus::Pending,
        };

        $paidAt = $paymentStatus === PaymentRecordStatus::Paid
            ? $placedAt->copy()->addMinutes(5)
            : null;

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'culqi',
            'method' => $method,
            'status' => $paymentStatus,
            'amount_cents' => (int) round(((float) $order->total_amount) * 100),
            'currency' => $order->currency ?: 'PEN',
            'culqi_charge_id' => $paymentStatus === PaymentRecordStatus::Paid
                ? 'chr_seed_'.$order->id
                : null,
            'culqi_order_id' => $method->isAsync() ? 'ord_seed_'.$order->id : null,
            'payment_code' => $method === PaymentMethodEnum::PagoEfectivo ? 'CIP-'.$order->id : null,
            'paid_at' => $paidAt,
            'created_at' => $placedAt,
            'updated_at' => $paidAt ?? $placedAt,
        ]);
    }

    private function recordSaleExits(Order $order): void
    {
        $order->loadMissing('items');
        $register = app(RegisterInventoryMovementAction::class);

        foreach ($order->items as $item) {
            $register->registerSaleExit($order, $item);
        }
    }

    /**
     * Volúmenes orientados a destacados:
     * - Accesorios: MW-ACC-001 (45 u), MW-LUB-001 (32 u), MW-ACC-002 (28 u)
     * - Motos: MW-MOTO-004 (8 u), MW-MOTO-001 (5 u), MW-MOTO-002 (3 u)
     *
     * @return list<array{
     *     status: OrderStatus,
     *     payment_status: PaymentStatus,
     *     days_ago: int,
     *     lines: list<array{sku: string, qty: int}>
     * }>
     */
    private function orderDefinitions(): array
    {
        return [
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 45, 'lines' => [['sku' => 'MW-ACC-001', 'qty' => 12]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 40, 'lines' => [['sku' => 'MW-ACC-001', 'qty' => 10], ['sku' => 'MW-ACC-003', 'qty' => 2]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 35, 'lines' => [['sku' => 'MW-ACC-001', 'qty' => 8], ['sku' => 'MW-ACC-002', 'qty' => 6]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 30, 'lines' => [['sku' => 'MW-ACC-001', 'qty' => 9], ['sku' => 'MW-LUB-001', 'qty' => 4]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 28, 'lines' => [['sku' => 'MW-ACC-001', 'qty' => 6], ['sku' => 'MW-ACC-002', 'qty' => 8]]],
            ['status' => OrderStatus::Shipped, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 20, 'lines' => [['sku' => 'MW-ACC-002', 'qty' => 7], ['sku' => 'MW-LUB-001', 'qty' => 10]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 18, 'lines' => [['sku' => 'MW-LUB-001', 'qty' => 12], ['sku' => 'MW-LUB-002', 'qty' => 3]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 15, 'lines' => [['sku' => 'MW-LUB-001', 'qty' => 6], ['sku' => 'MW-ACC-002', 'qty' => 7]]],
            ['status' => OrderStatus::Processing, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 12, 'lines' => [['sku' => 'MW-REP-001', 'qty' => 5], ['sku' => 'MW-BAT-001', 'qty' => 2]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 10, 'lines' => [['sku' => 'MW-MOTO-004', 'qty' => 3], ['sku' => 'MW-ACC-001', 'qty' => 1]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 8, 'lines' => [['sku' => 'MW-MOTO-004', 'qty' => 2], ['sku' => 'MW-MOTO-001', 'qty' => 2]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 6, 'lines' => [['sku' => 'MW-MOTO-001', 'qty' => 2], ['sku' => 'MW-MOTO-002', 'qty' => 1]]],
            ['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 4, 'lines' => [['sku' => 'MW-MOTO-004', 'qty' => 3], ['sku' => 'MW-MOTO-002', 'qty' => 2]]],
            ['status' => OrderStatus::Paid, 'payment_status' => PaymentStatus::Paid, 'days_ago' => 2, 'lines' => [['sku' => 'MW-MOTO-001', 'qty' => 1], ['sku' => 'MW-NEU-001', 'qty' => 2]]],
            // Cancelado: no debe sumar en productos destacados
            ['status' => OrderStatus::Cancelled, 'payment_status' => PaymentStatus::Failed, 'days_ago' => 1, 'lines' => [['sku' => 'MW-ACC-001', 'qty' => 50]]],
        ];
    }
}
