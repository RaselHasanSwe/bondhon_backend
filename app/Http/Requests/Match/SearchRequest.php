<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gender'         => ['nullable', 'in:male,female'],
            'age_min'        => ['nullable', 'integer', 'min:18', 'max:80'],
            'age_max'        => ['nullable', 'integer', 'min:18', 'max:80', 'gte:age_min'],
            'religion'       => ['nullable', 'string', 'max:100'],
            'caste'          => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', 'string', 'in:never_married,divorced,widowed,awaiting_divorce'],
            'height_min'     => ['nullable', 'integer', 'min:100', 'max:250'],
            'height_max'     => ['nullable', 'integer', 'min:100', 'max:250', 'gte:height_min'],
            'education'      => ['nullable', 'string', 'max:100'],
            'profession'     => ['nullable', 'string', 'max:100'],
            'income_min'     => ['nullable', 'integer', 'min:0'],
            'income_max'     => ['nullable', 'integer', 'min:0', 'gte:income_min'],
            'country'        => ['nullable', 'string', 'max:100'],
            'city'           => ['nullable', 'string', 'max:100'],
            'diet'           => ['nullable', 'string', 'in:vegetarian,non_vegetarian,vegan,jain'],
            'profile_id'     => ['nullable', 'string', 'regex:/^BON-\d+$/'],
            'query'          => ['nullable', 'string', 'max:255'],
            'page'           => ['nullable', 'integer', 'min:1'],
        ];
    }
}

