<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reported_id' => ['required', 'integer', 'exists:users,id'],
            'reason'      => ['required', 'string', 'in:fake_profile,inappropriate_photo,abusive,spam,other'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reported_id.exists' => 'The reported user does not exist.',
            'reason.in'          => 'Invalid report reason. Must be one of: fake_profile, inappropriate_photo, abusive, spam, other.',
        ];
    }
}

