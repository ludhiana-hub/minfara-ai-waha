<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('notification_template')?->id;

        return [
            'name'         => ['required', 'string', 'max:100'],
            'trigger_key'  => [
                'required', 'string', 'max:100',
                Rule::unique('notification_templates', 'trigger_key')->ignore($id),
            ],
            'message_body' => ['required', 'string', 'min:5'],
            'is_active'    => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Nama template wajib diisi.',
            'trigger_key.required'  => 'Trigger key wajib diisi.',
            'trigger_key.unique'    => 'Trigger key sudah digunakan oleh template lain.',
            'message_body.required' => 'Isi pesan wajib diisi.',
            'message_body.min'      => 'Isi pesan minimal 5 karakter.',
        ];
    }
}
