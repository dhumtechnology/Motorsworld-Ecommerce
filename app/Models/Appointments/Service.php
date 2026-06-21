<?php

namespace App\Models\Appointments;

use App\Enums\Appointments\ServiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'appointment_id',
    'service_type_id',
    'price',
    'currency',
    'status',
    'started_at',
    'completed_at',
    'cancelled_at',
    'refunded_at',
    'partially_refunded_at',
])]
class Service extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<ServiceType, $this>
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => ServiceStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'partially_refunded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
