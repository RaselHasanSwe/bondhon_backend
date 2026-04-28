<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'],
            'gender'             => ['required', 'in:male,female'],
            'profile_created_by' => ['required', 'in:self,parents,siblings'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'  => 'This email address is already registered.',
            'gender.in'     => 'Gender must be male or female.',
            'profile_created_by.in' => 'Profile created by must be self, parents, or siblings.',
        ];
    }
}

