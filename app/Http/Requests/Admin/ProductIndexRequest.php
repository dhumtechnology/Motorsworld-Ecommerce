<?php

namespace App\Http\Requests\Admin;

use App\Enums\Products\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
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
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['integer', 'exists:brands,id'],
            'models' => ['nullable', 'array'],
            'models.*' => ['integer', 'exists:models,id'],
            'status' => ['nullable', 'string', Rule::enum(ProductStatus::class)],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
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

    public function status(): ?ProductStatus
    {
        $status = $this->input('status');

        if ($status === null || $status === '') {
            return null;
        }

        return ProductStatus::tryFrom((string) $status);
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null
            || $this->categoryIds() !== []
            || $this->brandIds() !== []
            || $this->modelIds() !== []
            || $this->status() !== null;
    }

    /**
     * @return list<int>
     */
    private function normalizeIdList(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
