<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountDisableRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitAccountDisableRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', Rule::in(AccountDisableRequestType::values())],
            'message'      => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.in' => 'Invalid request type. Must be personal_reason or got_married_through_platform.',
            'message.min'     => 'Please provide at least 10 characters explaining your reason.',
        ];
    }
}
