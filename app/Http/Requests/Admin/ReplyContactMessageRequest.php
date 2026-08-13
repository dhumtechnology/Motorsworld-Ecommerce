<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReplyContactMessageRequest extends FormRequest
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
            'admin_reply' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'admin_reply' => 'respuesta al cliente',
            'admin_notes' => 'notas internas',
        ];
    }

    public function reply(): string
    {
        return trim($this->string('admin_reply')->value());
    }

    public function adminNotes(): ?string
    {
        $notes = trim($this->string('admin_notes')->value());

        return $notes !== '' ? $notes : null;
    }
}
