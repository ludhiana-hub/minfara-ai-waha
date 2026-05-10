<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('faq')?->id;

        return [
            'command'        => ['required', 'string', 'max:20', 'unique:faq_menus,command' . ($id ? ',' . $id : '')],
            'title'          => ['required', 'string', 'max:100'],
            'content'        => ['required', 'string', 'min:10', 'max:4096'],
            'parent_command' => ['nullable', 'string', 'max:20', 'exists:faq_menus,command'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'command.required'       => 'Command wajib diisi.',
            'command.unique'         => 'Command sudah digunakan oleh menu lain.',
            'command.max'            => 'Command maksimal 20 karakter.',
            'title.required'         => 'Judul wajib diisi.',
            'content.required'       => 'Konten pesan wajib diisi.',
            'content.min'            => 'Konten pesan minimal 10 karakter.',
            'content.max'            => 'Konten pesan maksimal 4096 karakter (batas WhatsApp).',
            'parent_command.exists'  => 'Parent command tidak ditemukan.',
        ];
    }
}
