<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodIndexRequest extends FormRequest
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
            'is_active' => ['nullable', 'in:0,1'],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function isActiveFilter(): ?bool
    {
        $value = $this->input('is_active');

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value === '1';
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null || $this->isActiveFilter() !== null;
    }
}
