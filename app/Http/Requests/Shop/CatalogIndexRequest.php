<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'brand' => ['nullable', 'integer', 'exists:brands,id'],
            'model' => ['nullable', 'integer', 'exists:models,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function section(): string
    {
        return $this->input('section', 'accesorios');
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
