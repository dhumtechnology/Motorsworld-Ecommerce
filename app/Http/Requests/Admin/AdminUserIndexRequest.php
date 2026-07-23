<?php

namespace App\Http\Requests\Admin;

use App\Enums\Auth\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserIndexRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::enum(UserStatus::class)],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function status(): ?UserStatus
    {
        $status = $this->input('status');

        if ($status === null || $status === '') {
            return null;
        }

        return UserStatus::tryFrom((string) $status);
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null || $this->status() !== null;
    }
}
