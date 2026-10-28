<?php

namespace App\Mail;

use App\Enums\Payments\PaymentMethod;
use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CouponAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public const BRAND_NAME = 'Motoworld';

    public function __construct(
        public Coupon $coupon,
        public User $customer,
    ) {
        $this->coupon->loadMissing([
            'products:id,name',
            'categories:id,name',
            'brands:id,name',
            'departments:id,name',
            'provinces.department:id,name',
            'districts.province:id,name',
        ]);
        $this->customer->loadMissing('customerProfile');
    }

    public function envelope(): Envelope
    {
        $label = $this->coupon->hasTitle()
            ? (string) $this->coupon->name
            : $this->coupon->code;

        return new Envelope(
            subject: 'Tienes un cupón disponible — '.$label.' — '.self::BRAND_NAME,
        );
    }

    public function content(): Content
    {
        $profile = $this->customer->customerProfile;
        $firstName = trim((string) ($profile?->first_name ?? ''));

        if ($firstName === '') {
            $firstName = 'Motero';
        }

        return new Content(
            view: 'emails.coupon-assigned',
            with: [
                'firstName' => $firstName,
                'title' => $this->coupon->hasTitle() ? (string) $this->coupon->name : null,
                'reason' => filled($this->coupon->reason) ? (string) $this->coupon->reason : null,
                'code' => $this->coupon->code,
                'discount' => $this->coupon->discountLabel(),
                'startsAt' => $this->coupon->starts_at?->format('d/m/Y H:i') ?? '—',
                'endsAt' => $this->coupon->ends_at?->format('d/m/Y H:i') ?? '—',
                'ruleLines' => $this->ruleLines(),
                'shopUrl' => route('shop.home'),
                'checkoutUrl' => route('shop.checkout.show'),
                'logoPath' => public_path('images/logo.png'),
                'appName' => self::BRAND_NAME,
            ],
        );
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function ruleLines(): array
    {
        $coupon = $this->coupon;
        $lines = [];

        if ($coupon->min_products) {
            $lines[] = [
                'label' => 'Mínimo de productos',
                'value' => (string) $coupon->min_products,
            ];
        }

        if ($coupon->min_subtotal) {
            $lines[] = [
                'label' => 'Compra mínima',
                'value' => Currency::format((float) $coupon->min_subtotal, $coupon->min_subtotal_currency),
            ];
        }

        $payments = $coupon->allowedPaymentMethods();
        if ($payments !== []) {
            $lines[] = [
                'label' => 'Métodos de pago',
                'value' => collect($payments)
                    ->map(fn (string $method) => PaymentMethod::tryFrom($method)?->label() ?? $method)
                    ->implode(', '),
            ];
        }

        $zones = $coupon->zoneLabels();
        if ($zones !== []) {
            $lines[] = [
                'label' => 'Zonas de entrega',
                'value' => implode(', ', $zones),
            ];
        }

        if ($coupon->products->isNotEmpty()) {
            $lines[] = [
                'label' => 'Productos',
                'value' => $coupon->products->pluck('name')->implode(', '),
            ];
        }

        if ($coupon->categories->isNotEmpty()) {
            $lines[] = [
                'label' => 'Categorías',
                'value' => $coupon->categories->pluck('name')->implode(', '),
            ];
        }

        if ($coupon->brands->isNotEmpty()) {
            $lines[] = [
                'label' => 'Marcas',
                'value' => $coupon->brands->pluck('name')->implode(', '),
            ];
        }

        return $lines;
    }
}
