<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HomeBannerIndexRequest extends FormRequest
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
            'status' => ['nullable', 'in:active,scheduled,expired,inactive'],
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

        return in_array($status, ['active', 'scheduled', 'expired', 'inactive'], true) ? $status : null;
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null || $this->statusFilter() !== null;
    }
}
