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
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'min:1', 'exists:categories,id'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['integer', 'min:1', 'exists:brands,id'],
            'models' => ['nullable', 'array'],
            'models.*' => ['integer', 'min:1', 'exists:models,id'],
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
            'categories' => 'categorías',
            'categories.*' => 'categoría',
            'brands' => 'marcas',
            'brands.*' => 'marca',
            'models' => 'modelos',
            'models.*' => 'modelo',
            'search' => 'búsqueda',
            'page' => 'página',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $brandIds = $this->brandIds();
            $modelIds = $this->modelIds();

            if ($brandIds === [] || $modelIds === []) {
                return;
            }

            $invalidCount = VehicleModel::query()
                ->whereIn('id', $modelIds)
                ->whereNotIn('brand_id', $brandIds)
                ->count();

            if ($invalidCount > 0) {
                $validator->errors()->add(
                    'models',
                    'Uno o más modelos seleccionados no pertenecen a las marcas indicadas.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $normalized = [
            'categories' => $this->normalizeIdList(
                $this->input('categories', $this->input('category')),
            ),
            'brands' => $this->normalizeIdList(
                $this->input('brands', $this->input('brand')),
            ),
            'models' => $this->normalizeIdList(
                $this->input('models', $this->input('model')),
            ),
        ];

        if ($this->has('section')) {
            $section = strtolower(trim((string) $this->input('section')));

            $normalized['section'] = $section !== '' ? $section : null;
        }

        if ($this->has('search')) {
            $search = trim((string) $this->input('search'));

            $normalized['search'] = $search !== '' ? $search : null;
        }

        $this->merge($normalized);
    }

    public function section(): string
    {
        $section = $this->input('section');

        return in_array($section, ['motos', 'accesorios'], true) ? $section : 'accesorios';
    }

    /**
     * @return list<int>
     */
    public function categoryIds(): array
    {
        return $this->normalizeIdList($this->input('categories'));
    }

    /**
     * @return list<int>
     */
    public function brandIds(): array
    {
        return $this->normalizeIdList($this->input('brands'));
    }

    /**
     * @return list<int>
     */
    public function modelIds(): array
    {
        return $this->normalizeIdList($this->input('models'));
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search !== '' ? $search : null;
    }

    /**
     * @return list<int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        $ids = array_map(
            static fn (mixed $id): int => (int) $id,
            $value,
        );

        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));

        sort($ids);

        return $ids;
    }
}
