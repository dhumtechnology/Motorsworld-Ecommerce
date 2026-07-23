<?php

namespace App\Http\Requests\Admin;

use App\Enums\Payments\PaymentMethod as PaymentMethodEnum;
use App\Enums\Payments\PaymentRecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentIndexRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::enum(PaymentRecordStatus::class)],
            'method' => ['nullable', 'string', Rule::enum(PaymentMethodEnum::class)],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function status(): ?PaymentRecordStatus
    {
        $status = $this->input('status');

        if ($status === null || $status === '') {
            return null;
        }

        return PaymentRecordStatus::tryFrom((string) $status);
    }

    public function method(): ?PaymentMethodEnum
    {
        $method = $this->input('method');

        if ($method === null || $method === '') {
            return null;
        }

        return PaymentMethodEnum::tryFrom((string) $method);
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null
            || $this->status() !== null
            || $this->method() !== null;
    }
}
