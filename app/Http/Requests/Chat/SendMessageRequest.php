<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'      => ['required', 'in:text,image,document,voice'],
            'body'      => ['required_if:type,text', 'nullable', 'string', 'max:5000'],
            'file'      => ['required_unless:type,text', 'nullable', 'file', 'max:20480'],
        ];
    }
}

