<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'is_active'    => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Nama target wajib diisi.',
            'phone_number.required'  => 'Nomor WA wajib diisi.',
            'phone_number.regex'     => 'Format nomor tidak valid. Gunakan format 08xxx, 628xxx, atau +62xxx.',
        ];
    }
}
