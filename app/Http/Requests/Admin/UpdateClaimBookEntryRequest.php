<?php

namespace App\Http\Requests\Admin;

use App\Enums\Claims\ClaimBookStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClaimBookEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ClaimBookStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'admin_notes' => 'notas internas',
        ];
    }

    public function status(): ClaimBookStatus
    {
        return ClaimBookStatus::from($this->string('status')->value());
    }

    public function adminNotes(): ?string
    {
        $notes = trim($this->string('admin_notes')->value());

        return $notes !== '' ? $notes : null;
    }
}
