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
            'gender'          => ['nullable', 'in:male,female'],
            'age_min'         => ['nullable', 'integer', 'min:18', 'max:100'],
            'age_max'         => ['nullable', 'integer', 'min:18', 'max:100', 'gte:age_min'],
            'religion'        => ['nullable', 'string', 'max:100'],
            'caste'           => ['nullable', 'string', 'max:100'],
            'marital_status'  => ['nullable', 'string', 'in:never_married,divorced,widowed,awaiting_divorce'],
            'height_min'      => ['nullable', 'integer', 'min:100', 'max:250'],
            'height_max'      => ['nullable', 'integer', 'min:100', 'max:250', 'gte:height_min'],
            'education'       => ['nullable', 'string', 'max:100'],
            'profession'      => ['nullable', 'string', 'max:100'],
            'employed_in'     => ['nullable', 'string', 'in:private,government,business,self_employed,not_working'],
            'income_min'      => ['nullable', 'integer', 'min:0'],
            'income_max'      => ['nullable', 'integer', 'min:0', 'gte:income_min'],
            'country'         => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'city'            => ['nullable', 'string', 'max:100'],
            'nationality'     => ['nullable', 'string', 'max:100'],
            'residing_status' => ['nullable', 'string', 'in:citizen,permanent_resident,work_permit,student_visa,visitor_visa,refugee,other'],
            'diet'            => ['nullable', 'string', 'in:vegetarian,non_vegetarian,vegan,jain'],
            'smoking'         => ['nullable', 'string', 'in:non_smoker,smoker,occasionally'],
            'drinking'        => ['nullable', 'string', 'in:non_drinker,drinker,occasionally'],
            'body_type'       => ['nullable', 'string', 'in:slim,average,athletic,heavy'],
            'complexion'      => ['nullable', 'string', 'in:very_fair,fair,wheatish,dark'],
            'blood_group'     => ['nullable', 'string', 'max:10'],
            'mother_tongue'   => ['nullable', 'string', 'max:100'],
            'has_children'    => ['nullable', 'string', 'in:no,yes'],
            'profile_id'      => ['nullable', 'string', 'regex:/^BON-\d+$/i'],
            'query'           => ['nullable', 'string', 'max:255'],
            'sort'            => ['nullable', 'string', 'in:latest,age_asc,age_desc,completion'],
            'page'            => ['nullable', 'integer', 'min:1'],
        ];
    }
}

