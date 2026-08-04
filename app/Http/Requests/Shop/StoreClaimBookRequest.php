<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClaimBookRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'good_type' => ['required', Rule::in(['product', 'service'])],
            'good_description' => ['required', 'string', 'max:2000'],
            'claimed_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'claim_type' => ['required', Rule::in(['claim', 'complaint'])],
            'detail' => ['required', 'string', 'max:5000'],
            'consumer_request' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'document' => 'documento',
            'address' => 'domicilio',
            'phone' => 'teléfono',
            'email' => 'email',
            'good_type' => 'tipo de bien',
            'good_description' => 'descripción del bien',
            'claimed_amount' => 'monto reclamado',
            'claim_type' => 'tipo de reclamación',
            'detail' => 'detalle',
            'consumer_request' => 'pedido del consumidor',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function claimData(): array
    {
        $amount = $this->input('claimed_amount');

        return [
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'document' => trim((string) $this->input('document')),
            'address' => trim((string) $this->input('address')),
            'phone' => trim((string) $this->input('phone')),
            'email' => trim((string) $this->input('email')),
            'good_type' => (string) $this->input('good_type'),
            'good_description' => trim((string) $this->input('good_description')),
            'claimed_amount' => $amount === null || $amount === '' ? null : (float) $amount,
            'claim_type' => (string) $this->input('claim_type'),
            'detail' => trim((string) $this->input('detail')),
            'consumer_request' => trim((string) $this->input('consumer_request')),
        ];
    }
}
