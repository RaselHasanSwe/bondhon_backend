<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dob'            => ['nullable', 'date', 'before:-18 years'],
            'height_cm'      => ['nullable', 'integer', 'min:100', 'max:250'],
            'weight_kg'      => ['nullable', 'integer', 'min:30', 'max:200'],
            'complexion'     => ['nullable', 'in:very_fair,fair,wheatish,dark'],
            'blood_group'    => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],
            'marital_status' => ['nullable', 'in:never_married,divorced,widowed,awaiting_divorce'],
            'mother_tongue'  => ['nullable', 'string', 'max:100'],
            'nationality'    => ['nullable', 'string', 'max:100'],
            'country'        => ['nullable', 'string', 'max:100'],
            'state'          => ['nullable', 'string', 'max:100'],
            'city'           => ['nullable', 'string', 'max:100'],
            'about_me'       => ['nullable', 'string', 'max:2000'],

            // Religious details
            'religion'        => ['nullable', 'string', 'max:100'],
            'caste'           => ['nullable', 'string', 'max:100'],
            'sub_caste'       => ['nullable', 'string', 'max:100'],
            'gotra'           => ['nullable', 'string', 'max:100'],
            'manglik_status'  => ['nullable', 'in:yes,no,partial,dont_know'],

            // Family details
            'family_type'                 => ['nullable', 'in:joint,nuclear,extended'],
            'family_status'               => ['nullable', 'in:middle_class,upper_middle_class,rich,affluent'],
            'family_income_bdt_per_month' => ['nullable', 'integer', 'min:0'],
            'father_occupation'           => ['nullable', 'string', 'max:200'],
            'mother_occupation'           => ['nullable', 'string', 'max:200'],
            'brothers_count'              => ['nullable', 'integer', 'min:0', 'max:20'],
            'sisters_count'               => ['nullable', 'integer', 'min:0', 'max:20'],

            // Education & career
            'highest_education'  => ['nullable', 'string', 'max:200'],
            'college_university' => ['nullable', 'string', 'max:300'],
            'profession'         => ['nullable', 'string', 'max:200'],
            'employed_in'        => ['nullable', 'in:private,government,business,self_employed,not_working'],
            'annual_income_bdt'  => ['nullable', 'integer', 'min:0'],

            // Lifestyle
            'diet'            => ['nullable', 'in:vegetarian,non_vegetarian,vegan,jain'],
            'smoking'         => ['nullable', 'in:non_smoker,smoker,occasionally'],
            'drinking'        => ['nullable', 'in:non_drinker,drinker,occasionally'],
            'hobbies'         => ['nullable', 'array'],
            'languages_known' => ['nullable', 'array'],

            // Horoscope
            'birth_place' => ['nullable', 'string', 'max:200'],
            'birth_time'  => ['nullable', 'date_format:H:i'],
            'rashi'       => ['nullable', 'string', 'max:100'],
            'nakshatra'   => ['nullable', 'string', 'max:100'],
            'manglik'     => ['nullable', 'boolean'],

            // Privacy settings
            'privacy_settings'                    => ['nullable', 'array'],
            'privacy_settings.show_photo_to'      => ['nullable', 'in:all,connections_only,none'],
            'privacy_settings.show_phone_to'      => ['nullable', 'in:connections_only,none'],
            'privacy_settings.show_email_to'      => ['nullable', 'in:none'],
            'privacy_settings.show_online_status' => ['nullable', 'boolean'],
        ];
    }
}

