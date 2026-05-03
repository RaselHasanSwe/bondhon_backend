<?php

namespace App\Http\Requests\Call;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'type'        => ['required', 'string', 'in:audio,video'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.required' => 'A receiver must be specified.',
            'receiver_id.exists'   => 'The specified receiver does not exist.',
            'type.in'              => 'Call type must be audio or video.',
        ];
    }
}

