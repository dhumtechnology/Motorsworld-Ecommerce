<?php

namespace App\Http\Requests\Admin;

use App\Enums\Products\ProductStatus;
use App\Http\Requests\Admin\Concerns\ParsesProductVariantPayload;
use App\Models\Products\ProductVariant;
use App\Models\Products\VehicleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
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
        return [
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
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
            'default_available_stock' => ['nullable', 'integer', 'min:0'],
            'default_images' => ['nullable', 'array', 'max:13'],
            'default_images.*' => ['image', 'max:5120'],
            'default_primary_image' => ['nullable', 'image', 'max:5120'],
            'default_secondary_images' => ['nullable', 'array', 'max:12'],
            'default_secondary_images.*' => ['image', 'max:5120'],
            'default_remove_image_ids' => ['nullable', 'array'],
            'default_remove_image_ids.*' => ['integer', 'exists:product_images,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
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

            if (ProductVariant::query()->where('sku', $sku)->exists()) {
                $validator->errors()->add(
                    "variants.{$index}.sku",
                    'Ese SKU / código de barras ya está en uso.',
                );
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'Indica el SKU / código de barras.',
            'sku.unique' => 'Ese SKU / código de barras ya está en uso.',
            'category_id.required' => 'Selecciona una categoría.',
            'model_id.exists' => 'El modelo no pertenece a la marca seleccionada.',
            'variants.*.sku.required_with' => 'Indica el SKU / código de barras de cada variante.',
            'variants.*.sku.distinct' => 'Cada variante debe tener un SKU / código de barras distinto.',
            'variants.*.available_stock.required' => 'Indica el stock de cada combinación.',
        ];
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

    public function technicalSheet(): ?UploadedFile
    {
        /** @var UploadedFile|null $file */
        $file = $this->file('technical_sheet');

        return $file instanceof UploadedFile ? $file : null;
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
