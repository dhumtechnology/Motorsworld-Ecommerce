<?php

namespace App\Mail;

use App\Enums\Orders\OrderStatus;
use App\Models\Orders\Order;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(
        public Order $order,
        public ?string $note = null,
    ) {
        $this->order->loadMissing([
            'user.customerProfile',
            'shippingAddress',
            'items.product:id,name,sku',
            'items.variant',
        ]);
    }

    public function envelope(): Envelope
    {
        $status = $this->resolveStatus();

        return new Envelope(
            subject: "Tu pedido #{$this->order->id} está {$status->label()} — ".self::BRAND_NAME,
        );
    }

    public function content(): Content
    {
        $order = $this->order;
        $status = $this->resolveStatus();
        $profile = $order->user?->customerProfile;
        $firstName = trim((string) ($profile?->first_name ?? ''));

        if ($firstName === '') {
            $firstName = 'Motero';
        }

        $items = $order->items->map(function ($item) use ($order) {
            $currency = $item->currency ?? $order->currency;
            $lineTotal = (float) $item->unit_price * (int) $item->quantity;

            return [
                'name' => $item->product?->name ?? 'Producto',
                'variant' => $item->variant?->colorLabel(),
                'quantity' => (int) $item->quantity,
                'line_total' => Currency::format($lineTotal, $currency),
            ];
        })->values()->all();

        return new Content(
            view: 'emails.order-status-changed',
            with: [
                'firstName' => $firstName,
                'orderId' => $order->id,
                'statusLabel' => $status->label(),
                'statusMessage' => $this->statusMessage($status),
                'note' => filled($this->note) ? trim((string) $this->note) : null,
                'items' => $items,
                'orderTotal' => Currency::format((float) $order->total_amount, $order->currency),
                'orderUrl' => route('shop.checkout.orders.show', $order),
                'accountUrl' => route('shop.account.show'),
                'shopUrl' => route('shop.home'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }

    private function resolveStatus(): OrderStatus
    {
        return $this->order->status instanceof OrderStatus
            ? $this->order->status
            : OrderStatus::from((string) $this->order->status);
    }

    private function statusMessage(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Shipped => 'Tu pedido ya fue enviado y está en camino.',
            OrderStatus::Delivered => 'Tu pedido fue entregado. ¡Gracias por comprar en Motoworld!',
            OrderStatus::Cancelled => 'Tu pedido fue cancelado. Si tienes dudas, contáctanos.',
            OrderStatus::Refunded => 'Tu pedido fue reembolsado. El dinero se procesará según el medio de pago utilizado.',
            default => 'El estado de tu pedido fue actualizado.',
        };
    }
}
