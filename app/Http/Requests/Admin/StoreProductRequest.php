<?php

namespace App\Http\Requests\Admin;

use App\Enums\Products\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'additional_information' => ['nullable', 'string'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => [
                'nullable',
                'integer',
                Rule::exists('models', 'id')->when(
                    $this->filled('brand_id'),
                    fn ($rule) => $rule->where('brand_id', (int) $this->input('brand_id')),
                ),
            ],
            'available_stock' => ['required', 'integer', 'min:0'],
            'primary_image' => ['nullable', 'image', 'max:5120'],
            'secondary_images' => ['nullable', 'array', 'max:12'],
            'secondary_images.*' => ['image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'Ya existe un producto con ese SKU.',
            'category_id.required' => 'Selecciona una categoría.',
            'model_id.exists' => 'El modelo no pertenece a la marca seleccionada.',
            'primary_image.image' => 'La imagen principal debe ser un archivo de imagen válido.',
            'secondary_images.*.image' => 'Cada imagen secundaria debe ser un archivo de imagen válido.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productAttributes(): array
    {
        return [
            'sku' => trim((string) $this->input('sku')),
            'name' => trim((string) $this->input('name')),
            'description' => $this->nullableString('description'),
            'additional_information' => $this->nullableString('additional_information'),
            'price_amount' => $this->input('price_amount'),
            'currency' => 'PEN',
            'status' => $this->input('status'),
            'category_id' => (int) $this->input('category_id'),
            'model_id' => $this->filled('model_id') ? (int) $this->input('model_id') : null,
        ];
    }

    public function availableStock(): int
    {
        return (int) $this->input('available_stock', 0);
    }

    public function primaryImage(): ?UploadedFile
    {
        /** @var UploadedFile|null $file */
        $file = $this->file('primary_image');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * @return list<UploadedFile>
     */
    public function secondaryImages(): array
    {
        $files = $this->file('secondary_images', []);

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            static fn ($file): bool => $file instanceof UploadedFile,
        ));
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
