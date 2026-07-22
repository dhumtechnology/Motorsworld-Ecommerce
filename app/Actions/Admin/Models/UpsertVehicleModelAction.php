<?php

namespace App\Actions\Admin\Models;

use App\Models\Products\VehicleModel;
use Illuminate\Support\Facades\DB;

class UpsertVehicleModelAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?VehicleModel $model = null): VehicleModel
    {
        return DB::transaction(function () use ($attributes, $model) {
            if ($model === null) {
                $model = VehicleModel::query()->create($attributes);
            } else {
                $model->update($attributes);
            }

            return $model->fresh(['brand']);
        });
    }
}
