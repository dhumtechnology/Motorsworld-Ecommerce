<?php

namespace App\Actions\Admin\ServiceTypes;

use App\Models\Appointments\ServiceType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertServiceTypeAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        array $attributes,
        ?ServiceType $serviceType = null,
        ?UploadedFile $image = null,
        bool $removeImage = false,
    ): ServiceType {
        return DB::transaction(function () use ($attributes, $serviceType, $image, $removeImage) {
            if ($serviceType === null) {
                $serviceType = ServiceType::query()->create($attributes);
            } else {
                $serviceType->update($attributes);
            }

            if ($removeImage && $serviceType->image) {
                $this->deleteStoredFile($serviceType->image);
                $serviceType->forceFill(['image' => null])->save();
            }

            if ($image !== null) {
                if ($serviceType->image) {
                    $this->deleteStoredFile($serviceType->image);
                }

                $storedPath = $image->store("service-types/{$serviceType->id}", 'public');
                $serviceType->forceFill(['image' => '/storage/'.$storedPath])->save();
            }

            return $serviceType->fresh();
        });
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '' || str_contains($path, '://')) {
            return;
        }

        $relative = str_starts_with($path, '/storage/')
            ? substr($path, strlen('/storage/'))
            : (str_starts_with($path, 'storage/') ? substr($path, strlen('storage/')) : null);

        if ($relative === null) {
            return;
        }

        Storage::disk('public')->delete($relative);
    }
}
