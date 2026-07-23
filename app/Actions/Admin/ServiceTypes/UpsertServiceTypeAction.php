<?php

namespace App\Actions\Admin\ServiceTypes;

use App\Models\Appointments\ServiceType;
use Illuminate\Support\Facades\DB;

class UpsertServiceTypeAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?ServiceType $serviceType = null): ServiceType
    {
        return DB::transaction(function () use ($attributes, $serviceType) {
            if ($serviceType === null) {
                $serviceType = ServiceType::query()->create($attributes);
            } else {
                $serviceType->update($attributes);
            }

            return $serviceType->fresh();
        });
    }
}
