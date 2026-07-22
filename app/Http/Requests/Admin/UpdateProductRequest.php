<?php

namespace App\Http\Requests\Admin;

use App\Enums\Products\ProductStatus;
use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
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
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => [
                'integer',
                Rule::exists('product_images', 'id')->where('product_id', $product->id),
            ],
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

    /**
     * @return list<int>
     */
    public function removeImageIds(): array
    {
        $ids = $this->input('remove_image_ids', []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
