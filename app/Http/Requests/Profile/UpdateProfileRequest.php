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
            // User-level fields
            'name'               => ['nullable', 'string', 'max:100'],
            'profile_created_by' => ['nullable', 'in:self,parents,siblings,relative,friend,other'],

            // Basic profile
            'nick_name'          => ['nullable', 'string', 'max:100'],
            'profile_created_for'=> ['nullable', 'in:self,son,daughter,brother,sister,relative'],
            'looking_for'        => ['nullable', 'in:bride,groom'],
            'dob'                => ['nullable', 'date', 'before:-18 years'],
            'height_cm'          => ['nullable', 'integer', 'min:100', 'max:250'],
            'weight_kg'          => ['nullable', 'integer', 'min:30', 'max:200'],
            'body_type'          => ['nullable', 'in:slim,average,athletic,heavy'],
            'eye_color'          => ['nullable', 'string', 'max:50'],
            'hair_color'         => ['nullable', 'string', 'max:50'],
            'complexion'         => ['nullable', 'in:very_fair,fair,wheatish,dark'],
            'blood_group'        => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],
            'marital_status'     => ['nullable', 'in:never_married,divorced,widowed,awaiting_divorce'],
            'disability'         => ['nullable', 'string', 'max:50'],
            'mother_tongue'      => ['nullable', 'string', 'max:100'],
            'nationality'        => ['nullable', 'string', 'max:100'],
            'country'            => ['nullable', 'string', 'max:100'],
            'state'              => ['nullable', 'string', 'max:100'],
            'city'               => ['nullable', 'string', 'max:100'],
            'postal_code'        => ['nullable', 'string', 'max:20'],
            'residing_status'    => ['nullable', 'in:citizen,permanent_resident,work_permit,student_visa,visitor_visa,refugee,other'],
            'about_me'           => ['nullable', 'string', 'max:2000'],
            'what_looking_for'   => ['nullable', 'string', 'max:2000'],

            // Religious details
            'religion'        => ['nullable', 'string', 'max:100'],
            'caste'           => ['nullable', 'string', 'max:100'],
            'sub_caste'       => ['nullable', 'string', 'max:100'],
            'gotra'           => ['nullable', 'string', 'max:100'],
            'manglik_status'  => ['nullable', 'in:yes,no,partial,dont_know'],
            'religiousness'   => ['nullable', 'in:very_religious,religious,moderate,not_religious'],
            'pray'            => ['nullable', 'in:always,usually,sometimes,rarely,never'],

            // Family details
            'family_type'                 => ['nullable', 'in:joint,nuclear,extended'],
            'family_status'               => ['nullable', 'in:middle_class,upper_middle_class,rich,affluent'],
            'family_income_bdt_per_month' => ['nullable', 'integer', 'min:0'],
            'father_occupation'           => ['nullable', 'string', 'max:200'],
            'mother_occupation'           => ['nullable', 'string', 'max:200'],
            'brothers_count'              => ['nullable', 'integer', 'min:0', 'max:20'],
            'sisters_count'               => ['nullable', 'integer', 'min:0', 'max:20'],
            'has_children'                => ['nullable', 'in:no,yes'],
            'child_living_status'         => ['nullable', 'string', 'max:100'],
            'family_values'               => ['nullable', 'in:traditional,moderate,liberal,religious'],
            'sibling_position'            => ['nullable', 'integer', 'min:1', 'max:20'],

            // Education & career
            'highest_education'    => ['nullable', 'string', 'max:200'],
            'college_university'   => ['nullable', 'string', 'max:300'],
            'institution_name_year'=> ['nullable', 'string', 'max:300'],
            'employer_name'        => ['nullable', 'string', 'max:200'],
            'job_location'         => ['nullable', 'string', 'max:200'],
            'designation'          => ['nullable', 'string', 'max:200'],
            'experience_years'     => ['nullable', 'integer', 'min:0', 'max:50'],
            'profession'           => ['nullable', 'string', 'max:200'],
            'employed_in'          => ['nullable', 'in:private,government,business,self_employed,not_working'],
            'annual_income_bdt'    => ['nullable', 'integer', 'min:0'],

            // Lifestyle
            'diet'            => ['nullable', 'in:vegetarian,non_vegetarian,vegan,jain'],
            'smoking'         => ['nullable', 'in:non_smoker,smoker,occasionally'],
            'drinking'        => ['nullable', 'in:non_drinker,drinker,occasionally'],
            'eye_wear'        => ['nullable', 'in:none,glasses,contact_lens'],
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

