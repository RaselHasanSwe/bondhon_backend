<?php

namespace App\Http\Requests\Call;

use Illuminate\Foundation\Http\FormRequest;

class SignalCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'type'       => ['required', 'string', 'in:offer,answer,ice-candidate'],
            'payload'    => ['required', 'array'],
        ];
    }
}

