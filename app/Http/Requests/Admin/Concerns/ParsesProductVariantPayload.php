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

            $removeImageIds = $row['remove_image_ids'] ?? [];
            if (! is_array($removeImageIds)) {
                $removeImageIds = [];
            }

            $imageOrder = $row['image_order'] ?? [];
            if (! is_array($imageOrder)) {
                $imageOrder = [];
            }
            $imageOrder = array_values(array_filter(array_map(
                static fn ($token) => trim((string) $token),
                $imageOrder,
            )));

            $newImages = $this->normalizeUploadedFiles(
                $this->file("variants.{$index}.images", []),
            );

            $payload[] = [
                'id' => $hasIdentity ? (int) $row['id'] : null,
                'sku' => trim((string) ($row['sku'] ?? '')),
                'color_ids' => array_values(array_map('intval', $colorIds)),
                'new_colors' => $newColors,
                'available_stock' => (int) ($row['available_stock'] ?? 0),
                'image_order' => $imageOrder,
                'new_images' => $newImages,
                'remove_image_ids' => array_values(array_unique(array_map('intval', $removeImageIds))),
            ];
        }

        return $payload;
    }

    /**
     * @return array{
     *     available_stock: int,
     *     image_order: list<string>,
     *     new_images: list<UploadedFile>,
     *     remove_image_ids: list<int>
     * }
     */
    public function defaultGalleryPayload(): array
    {
        $removeImageIds = $this->input('default_remove_image_ids', []);
        if (! is_array($removeImageIds)) {
            $removeImageIds = [];
        }

        $imageOrder = $this->input('default_image_order', []);
        if (! is_array($imageOrder)) {
            $imageOrder = [];
        }
        $imageOrder = array_values(array_filter(array_map(
            static fn ($token) => trim((string) $token),
            $imageOrder,
        )));

        $newImages = $this->normalizeUploadedFiles($this->file('default_images', []));

        return [
            'available_stock' => max(0, (int) $this->input('default_available_stock', 0)),
            'image_order' => $imageOrder,
            'new_images' => $newImages,
            'remove_image_ids' => array_values(array_unique(array_map('intval', $removeImageIds))),
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    private function normalizeUploadedFiles(mixed $files): array
    {
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            static fn ($file): bool => $file instanceof UploadedFile,
        ));
    }
}
