<?php

namespace App\Http\Requests\Admin;

use App\Enums\Products\ProductStatus;
use App\Http\Requests\Admin\Concerns\ParsesProductVariantPayload;
use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use ParsesProductVariantPayload;

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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'additional_information' => ['nullable', 'string'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', Rule::in(['PEN', 'USD'])],
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
            'technical_sheet' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_technical_sheet' => ['nullable', 'boolean'],
            'default_available_stock' => ['nullable', 'integer', 'min:0'],
            'default_primary_image' => ['nullable', 'image', 'max:5120'],
            'default_secondary_images' => ['nullable', 'array', 'max:12'],
            'default_secondary_images.*' => ['image', 'max:5120'],
            'default_remove_image_ids' => ['nullable', 'array'],
            'default_remove_image_ids.*' => ['integer', 'exists:product_images,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $product->id),
            ],
            'variants.*.color_ids' => ['nullable', 'array'],
            'variants.*.color_ids.*' => ['integer', 'exists:colors,id'],
            'variants.*.new_colors' => ['nullable', 'array'],
            'variants.*.new_colors.*.name' => ['nullable', 'string', 'max:100'],
            'variants.*.new_colors.*.hex' => ['nullable', 'string', 'max:7'],
            'variants.*.available_stock' => ['required', 'integer', 'min:0'],
            'variants.*.primary_image' => ['nullable', 'image', 'max:5120'],
            'variants.*.secondary_images' => ['nullable', 'array', 'max:12'],
            'variants.*.secondary_images.*' => ['image', 'max:5120'],
            'variants.*.remove_image_ids' => ['nullable', 'array'],
            'variants.*.remove_image_ids.*' => ['integer', 'exists:product_images,id'],
            'remove_variant_ids' => ['nullable', 'array'],
            'remove_variant_ids.*' => [
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $product->id),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'description' => $this->nullableString('description'),
            'additional_information' => $this->nullableString('additional_information'),
            'price_amount' => $this->input('price_amount'),
            'currency' => strtoupper((string) $this->input('currency', 'PEN')),
            'status' => $this->input('status'),
            'category_id' => (int) $this->input('category_id'),
            'model_id' => $this->filled('model_id') ? (int) $this->input('model_id') : null,
        ];
    }

    /**
     * @return list<int>
     */
    public function removeVariantIds(): array
    {
        $ids = $this->input('remove_variant_ids', []);
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function technicalSheet(): ?UploadedFile
    {
        /** @var UploadedFile|null $file */
        $file = $this->file('technical_sheet');

        return $file instanceof UploadedFile ? $file : null;
    }

    public function shouldRemoveTechnicalSheet(): bool
    {
        return $this->boolean('remove_technical_sheet');
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
