<?php

namespace App\Actions\Admin\ServicePackages;

use App\Models\Appointments\ServicePackage;
use Illuminate\Support\Facades\DB;

class UpsertServicePackageAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?ServicePackage $servicePackage = null): ServicePackage
    {
        return DB::transaction(function () use ($attributes, $servicePackage) {
            if ($servicePackage === null) {
                $servicePackage = ServicePackage::query()->create($attributes);
            } else {
                $servicePackage->update($attributes);
            }

            return $servicePackage->fresh(['serviceType']);
        });
    }
}
