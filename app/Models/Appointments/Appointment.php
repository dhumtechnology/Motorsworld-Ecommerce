<?php

namespace App\Models\Appointments;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Auth\User;
use App\Models\Products\Brand;
use App\Models\Products\VehicleModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'customer_name',
    'customer_document',
    'customer_phone',
    'customer_email',
    'appointment_at',
    'brand_id',
    'vehicle_model_id',
    'km',
    'plate',
    'service_type_id',
    'service_package_id',
    'comments',
    'cancellation_reason',
    'status',
    'charged_amount',
    'charged_currency',
    'exchange_rate_buy',
    'exchange_rate_sell',
    'exchange_rate_date',
    'attended_at',
])]
class Appointment extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    /**
     * @return BelongsTo<ServiceType, $this>
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * @return BelongsTo<ServicePackage, $this>
     */
    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function displayCustomerName(): string
    {
        $profile = $this->user?->customerProfile;
        $fromProfile = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));

        if ($fromProfile !== '') {
            return $fromProfile;
        }

        $fromAppointment = trim((string) ($this->customer_name ?? ''));

        return $fromAppointment !== '' ? $fromAppointment : 'Sin nombre';
    }

    public function displayCustomerEmail(): string
    {
        return $this->user?->email
            ?: (string) ($this->customer_email ?? '')
            ?: '—';
    }

    public function displayCustomerDocument(): string
    {
        return $this->user?->customerProfile?->document
            ?: (string) ($this->customer_document ?? '')
            ?: '—';
    }

    public function displayCustomerPhone(): string
    {
        return $this->user?->customerProfile?->phone
            ?: (string) ($this->customer_phone ?? '')
            ?: '—';
    }

    public function revenueAmount(): float
    {
        if ($this->charged_amount !== null) {
            return (float) $this->charged_amount;
        }

        if ($this->relationLoaded('services') && $this->services->isNotEmpty()) {
            return round((float) $this->services->sum('price'), 2);
        }

        return (float) ($this->servicePackage?->price ?? 0);
    }

    public function revenueCurrency(): string
    {
        if (filled($this->charged_currency)) {
            return \App\Support\Currency::normalize($this->charged_currency);
        }

        if ($this->relationLoaded('services') && $this->services->isNotEmpty()) {
            return \App\Support\Currency::normalize($this->services->first()?->currency);
        }

        return \App\Support\Currency::normalize($this->servicePackage?->currency);
    }

    public function amountIn(string $targetCurrency): float
    {
        return \App\Support\Currency::convert(
            $this->revenueAmount(),
            $this->revenueCurrency(),
            $targetCurrency,
            $this->exchange_rate_sell !== null ? (float) $this->exchange_rate_sell : null,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_at' => 'datetime',
            'km' => 'decimal:2',
            'status' => AppointmentStatus::class,
            'charged_amount' => 'decimal:2',
            'exchange_rate_buy' => 'decimal:4',
            'exchange_rate_sell' => 'decimal:4',
            'exchange_rate_date' => 'date',
            'attended_at' => 'datetime',
        ];
    }
}
