<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServicePackageIndexRequest extends FormRequest
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
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'is_active' => ['nullable', 'in:0,1'],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function serviceTypeId(): ?int
    {
        $value = $this->input('service_type_id');

        return $value !== null && $value !== '' ? (int) $value : null;
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
        return $this->searchTerm() !== null
            || $this->serviceTypeId() !== null
            || $this->isActiveFilter() !== null;
    }
}
