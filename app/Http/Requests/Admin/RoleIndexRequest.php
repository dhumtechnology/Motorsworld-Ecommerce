<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RoleIndexRequest extends FormRequest
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
        ];
    }

    public function searchTerm(): ?string
    {
        $search = $this->string('search')->trim()->toString();

        return $search !== '' ? $search : null;
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null;
    }
}
