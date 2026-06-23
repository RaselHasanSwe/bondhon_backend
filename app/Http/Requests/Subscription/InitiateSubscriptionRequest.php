<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class InitiateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'The selected subscription plan does not exist.',
        ];
    }
}

