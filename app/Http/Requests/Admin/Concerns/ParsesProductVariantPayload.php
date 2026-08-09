<?php

namespace App\Http\Requests\Admin\Concerns;

use Illuminate\Http\UploadedFile;

trait ParsesProductVariantPayload
{
    /**
     * @return list<array<string, mixed>>
     */
    public function variantsPayload(): array
    {
        $rows = $this->input('variants', []);
        if (! is_array($rows)) {
            return [];
        }

        $payload = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $colorIds = $row['color_ids'] ?? [];
            if (! is_array($colorIds)) {
                $colorIds = [];
            }

            $newColorsInput = $row['new_colors'] ?? [];
            $newColors = [];
            if (is_array($newColorsInput)) {
                foreach ($newColorsInput as $newColor) {
                    if (! is_array($newColor)) {
                        continue;
                    }
                    $name = trim((string) ($newColor['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $newColors[] = [
                        'name' => $name,
                        'hex' => isset($newColor['hex']) ? trim((string) $newColor['hex']) : null,
                    ];
                }
            }

            $hasIdentity = isset($row['id']) && $row['id'] !== '';
            if ($colorIds === [] && $newColors === [] && ! $hasIdentity) {
                continue;
            }

            $secondary = $this->file("variants.{$index}.secondary_images", []);
            if (! is_array($secondary)) {
                $secondary = [];
            }

            $removeImageIds = $row['remove_image_ids'] ?? [];
            if (! is_array($removeImageIds)) {
                $removeImageIds = [];
            }

            $primary = $this->file("variants.{$index}.primary_image");

            $payload[] = [
                'id' => $hasIdentity ? (int) $row['id'] : null,
                'color_ids' => array_values(array_map('intval', $colorIds)),
                'new_colors' => $newColors,
                'available_stock' => (int) ($row['available_stock'] ?? 0),
                'primary_image' => $primary instanceof UploadedFile ? $primary : null,
                'secondary_images' => array_values(array_filter(
                    $secondary,
                    static fn ($file): bool => $file instanceof UploadedFile,
                )),
                'remove_image_ids' => array_values(array_unique(array_map('intval', $removeImageIds))),
            ];
        }

        return $payload;
    }

    /**
     * @return array{
     *     available_stock: int,
     *     primary_image: UploadedFile|null,
     *     secondary_images: list<UploadedFile>,
     *     remove_image_ids: list<int>
     * }
     */
    public function defaultGalleryPayload(): array
    {
        $secondary = $this->file('default_secondary_images', []);
        if (! is_array($secondary)) {
            $secondary = [];
        }

        $removeImageIds = $this->input('default_remove_image_ids', []);
        if (! is_array($removeImageIds)) {
            $removeImageIds = [];
        }

        $primary = $this->file('default_primary_image');

        return [
            'available_stock' => max(0, (int) $this->input('default_available_stock', 0)),
            'primary_image' => $primary instanceof UploadedFile ? $primary : null,
            'secondary_images' => array_values(array_filter(
                $secondary,
                static fn ($file): bool => $file instanceof UploadedFile,
            )),
            'remove_image_ids' => array_values(array_unique(array_map('intval', $removeImageIds))),
        ];
    }
}
