<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InternalNotifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trigger_key'        => ['required', 'string', 'max:100'],
            'data'               => ['required', 'array'],
            'data.customer_name' => ['nullable', 'string', 'max:255'],
            'data.program_name'  => ['nullable', 'string', 'max:255'],
            'data.amount'        => ['nullable', 'string', 'max:100'],
            'data.transaction_id'=> ['nullable', 'string', 'max:100'],
            'data.status'        => ['nullable', 'string', 'max:50'],
            'data.product_url'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'trigger_key.required' => 'trigger_key wajib diisi.',
            'data.required'        => 'Field data wajib diisi.',
            'data.array'           => 'Field data harus berupa object/array.',
        ];
    }
}
