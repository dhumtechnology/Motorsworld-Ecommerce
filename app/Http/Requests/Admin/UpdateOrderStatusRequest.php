<?php

namespace App\Http\Requests\Admin;

use App\Enums\Orders\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'note' => 'nota',
        ];
    }

    public function orderStatus(): OrderStatus
    {
        return OrderStatus::from((string) $this->input('status'));
    }

    public function note(): ?string
    {
        $note = trim((string) $this->input('note', ''));

        return $note !== '' ? $note : null;
    }
}
