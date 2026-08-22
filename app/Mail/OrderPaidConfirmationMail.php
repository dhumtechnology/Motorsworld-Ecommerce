<?php

namespace App\Mail;

use App\Enums\Orders\FulfillmentMethod;
use App\Models\Orders\Order;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaidConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(public Order $order)
    {
        $this->order->loadMissing([
            'user.customerProfile',
            'shippingAddress',
            'items.product:id,name,sku',
            'items.variant',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de compra #'.$this->order->id.' — '.self::BRAND_NAME,
        );
    }

    public function content(): Content
    {
        $order = $this->order;
        $profile = $order->user?->customerProfile;
        $firstName = trim((string) ($profile?->first_name ?? ''));

        if ($firstName === '') {
            $firstName = 'Motero';
        }

        $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
        $isPickup = $order->fulfillment_method === FulfillmentMethod::Pickup;
        $shipping = $order->shippingAddress;

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

        $shippingLines = [];
        if ($isPickup) {
            $shippingLines[] = config('shop.contact.address') ?: 'Retiro en tienda Motoworld';
        } elseif ($shipping) {
            $shippingLines[] = (string) $shipping->line1;
            $cityLine = trim(($shipping->city ?? '').($shipping->postal_code ? ' · '.$shipping->postal_code : ''));
            if ($cityLine !== '') {
                $shippingLines[] = $cityLine;
            }
            if (filled($shipping->country)) {
                $shippingLines[] = (string) $shipping->country;
            }
        }

        return new Content(
            view: 'emails.order-paid-confirmation',
            with: [
                'firstName' => $firstName,
                'customerName' => $fullName !== '' ? $fullName : $firstName,
                'customerEmail' => $order->user?->email ?? '—',
                'customerPhone' => $profile?->phone ?: '—',
                'orderId' => $order->id,
                'orderTotal' => Currency::format((float) $order->total_amount, $order->currency),
                'items' => $items,
                'fulfillmentLabel' => $order->fulfillment_method?->label() ?? 'Entrega',
                'isPickup' => $isPickup,
                'shippingLines' => $shippingLines,
                'orderUrl' => route('shop.checkout.orders.show', $order),
                'accountUrl' => route('shop.account.show'),
                'shopUrl' => route('shop.home'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }
}
