<?php

namespace App\Http\Requests\Admin;

use App\Enums\Inventory\InventoryMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::enum(InventoryMovementType::class)],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['integer', 'exists:brands,id'],
            'models' => ['nullable', 'array'],
            'models.*' => ['integer', 'exists:models,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function type(): ?InventoryMovementType
    {
        $type = $this->input('type');

        if ($type === null || $type === '') {
            return null;
        }

        return InventoryMovementType::tryFrom((string) $type);
    }

    /**
     * @return list<int>
     */
    public function categoryIds(): array
    {
        return $this->normalizeIdList($this->input('categories', []));
    }

    /**
     * @return list<int>
     */
    public function brandIds(): array
    {
        return $this->normalizeIdList($this->input('brands', []));
    }

    /**
     * @return list<int>
     */
    public function modelIds(): array
    {
        return $this->normalizeIdList($this->input('models', []));
    }

    public function dateFrom(): ?string
    {
        $value = $this->input('date_from');

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function dateTo(): ?string
    {
        $value = $this->input('date_to');

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null
            || $this->type() !== null
            || $this->categoryIds() !== []
            || $this->brandIds() !== []
            || $this->modelIds() !== []
            || $this->dateFrom() !== null
            || $this->dateTo() !== null;
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }
}
