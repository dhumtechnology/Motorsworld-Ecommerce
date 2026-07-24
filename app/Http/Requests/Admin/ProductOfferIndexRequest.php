<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductOfferIndexRequest extends FormRequest
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
            'status' => ['nullable', 'in:active,scheduled,expired'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function statusFilter(): ?string
    {
        $status = $this->input('status');

        return in_array($status, ['active', 'scheduled', 'expired'], true) ? $status : null;
    }

    public function productId(): ?int
    {
        $id = $this->input('product_id');

        return is_numeric($id) ? (int) $id : null;
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null
            || $this->statusFilter() !== null
            || $this->productId() !== null;
    }
}
