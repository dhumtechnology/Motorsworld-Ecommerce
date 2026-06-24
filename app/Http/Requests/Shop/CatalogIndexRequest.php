<?php

namespace App\Http\Requests\Shop;

use App\Models\Products\VehicleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CatalogIndexRequest extends FormRequest
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
            'section' => ['nullable', 'string', Rule::in(['motos', 'accesorios'])],
            'category' => ['nullable', 'integer', 'min:1', 'exists:categories,id'],
            'brand' => ['nullable', 'integer', 'min:1', 'exists:brands,id'],
            'model' => ['nullable', 'integer', 'min:1', 'exists:models,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'section' => 'sección',
            'category' => 'categoría',
            'brand' => 'marca',
            'model' => 'modelo',
            'search' => 'búsqueda',
            'page' => 'página',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $brandId = $this->brandId();
            $modelId = $this->modelId();

            if ($brandId === null || $modelId === null) {
                return;
            }

            $modelBelongsToBrand = VehicleModel::query()
                ->whereKey($modelId)
                ->where('brand_id', $brandId)
                ->exists();

            if (! $modelBelongsToBrand) {
                $validator->errors()->add(
                    'model',
                    'El modelo seleccionado no pertenece a la marca indicada.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->has('section')) {
            $section = strtolower(trim((string) $this->input('section')));

            $normalized['section'] = $section !== '' ? $section : null;
        }

        if ($this->has('search')) {
            $search = trim((string) $this->input('search'));

            $normalized['search'] = $search !== '' ? $search : null;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    public function section(): string
    {
        $section = $this->input('section');

        return in_array($section, ['motos', 'accesorios'], true) ? $section : 'accesorios';
    }

    public function categoryId(): ?int
    {
        $value = $this->input('category');

        return $value !== null ? (int) $value : null;
    }

    public function brandId(): ?int
    {
        $value = $this->input('brand');

        return $value !== null ? (int) $value : null;
    }

    public function modelId(): ?int
    {
        $value = $this->input('model');

        return $value !== null ? (int) $value : null;
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search !== '' ? $search : null;
    }
}
