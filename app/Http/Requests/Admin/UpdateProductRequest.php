<?php

namespace App\Http\Requests\Admin;

use App\Enums\Products\ProductStatus;
use App\Http\Requests\Admin\Concerns\ParsesProductVariantPayload;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Products\VehicleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'default_images' => ['nullable', 'array', 'max:13'],
            'default_images.*' => ['image', 'max:5120'],
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
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100', 'distinct'],
            'variants.*.color_ids' => ['nullable', 'array'],
            'variants.*.color_ids.*' => ['integer', 'exists:colors,id'],
            'variants.*.new_colors' => ['nullable', 'array'],
            'variants.*.new_colors.*.name' => ['nullable', 'string', 'max:100'],
            'variants.*.new_colors.*.hex' => ['nullable', 'string', 'max:7'],
            'variants.*.available_stock' => ['required', 'integer', 'min:0'],
            'variants.*.images' => ['nullable', 'array', 'max:13'],
            'variants.*.images.*' => ['image', 'max:5120'],
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => trim((string) $this->input('sku', '')),
        ]);

        if ($this->filled('model_id') && ! $this->filled('brand_id')) {
            $brandId = VehicleModel::query()
                ->whereKey((int) $this->input('model_id'))
                ->value('brand_id');

            if ($brandId) {
                $this->merge(['brand_id' => (int) $brandId]);
            }
        }

        if (! $this->filled('brand_id')) {
            $this->merge(['model_id' => null]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateVariantSkus($validator);
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU / código de barras',
            'brand_id' => 'marca',
            'model_id' => 'modelo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'Indica el SKU / código de barras.',
            'sku.unique' => 'Ese SKU / código de barras ya está en uso.',
            'model_id.exists' => 'El modelo no pertenece a la marca seleccionada.',
            'variants.*.sku.required_with' => 'Indica el SKU / código de barras de cada variante.',
            'variants.*.sku.distinct' => 'Cada variante debe tener un SKU / código de barras distinto.',
        ];
    }

    private function validateVariantSkus(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $rows = $this->input('variants', []);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $variantId = isset($row['id']) && $row['id'] !== ''
                ? (int) $row['id']
                : null;

            $exists = ProductVariant::query()
                ->where('sku', $sku)
                ->when($variantId, fn ($query) => $query->where('id', '!=', $variantId))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    "variants.{$index}.sku",
                    'Ese SKU / código de barras ya está en uso.',
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function productAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'sku' => trim((string) $this->input('sku')),
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
