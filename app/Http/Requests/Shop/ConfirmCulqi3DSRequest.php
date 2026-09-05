<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCulqi3DSRequest extends FormRequest
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
            'authentication_3DS' => ['required', 'array'],
            'authentication_3DS.eci' => ['required', 'string', 'max:8'],
            'authentication_3DS.xid' => ['required', 'string', 'max:80'],
            'authentication_3DS.cavv' => ['required', 'string', 'max:80'],
            'authentication_3DS.protocolVersion' => ['nullable', 'string', 'max:16'],
            'authentication_3DS.directoryServerTransactionId' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function authentication3DS(): array
    {
        $auth = $this->input('authentication_3DS');

        return is_array($auth) ? $auth : [];
    }
}
