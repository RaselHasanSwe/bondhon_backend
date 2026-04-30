<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'age_min'              => ['nullable', 'integer', 'min:18', 'max:100'],
            'age_max'              => ['nullable', 'integer', 'min:18', 'max:100'],
            'height_min_cm'        => ['nullable', 'integer', 'min:100', 'max:250'],
            'height_max_cm'        => ['nullable', 'integer', 'min:100', 'max:250'],
            'marital_status'       => ['nullable', 'array'],
            'marital_status.*'     => ['string', 'in:never_married,divorced,widowed,awaiting_divorce'],
            'religion'             => ['nullable', 'array'],
            'religion.*'           => ['string'],
            'caste'                => ['nullable', 'array'],
            'caste.*'              => ['string'],
            'education'            => ['nullable', 'array'],
            'education.*'          => ['string'],
            'profession'           => ['nullable', 'array'],
            'profession.*'         => ['string'],
            'income_min_bdt'       => ['nullable', 'integer', 'min:0'],
            'income_max_bdt'       => ['nullable', 'integer', 'min:0'],
            'country'              => ['nullable', 'array'],
            'country.*'            => ['string'],
            'city'                 => ['nullable', 'array'],
            'city.*'               => ['string'],
            'diet'                 => ['nullable', 'array'],
            'diet.*'               => ['string', 'in:vegetarian,non_vegetarian,vegan,jain'],
            'smoking_acceptable'   => ['nullable', 'boolean'],
            'drinking_acceptable'  => ['nullable', 'boolean'],
        ];
    }
}

