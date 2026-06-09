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
            // ── Core ──────────────────────────────────────────────────────────
            'age_min'                   => ['nullable', 'integer', 'min:18', 'max:100'],
            'age_max'                   => ['nullable', 'integer', 'min:18', 'max:100'],
            'height_min_cm'             => ['nullable', 'integer', 'min:100', 'max:250'],
            'height_max_cm'             => ['nullable', 'integer', 'min:100', 'max:250'],
            'marital_status'            => ['nullable', 'array'],
            'marital_status.*'          => ['string', 'in:never_married,divorced,widowed,awaiting_divorce'],
            'religion'                  => ['nullable', 'array'],
            'religion.*'                => ['string'],
            'caste'                     => ['nullable', 'array'],
            'caste.*'                   => ['string'],
            'education'                 => ['nullable', 'array'],
            'education.*'               => ['string'],
            'profession'                => ['nullable', 'array'],
            'profession.*'              => ['string'],
            'income_min_bdt'            => ['nullable', 'integer', 'min:0'],
            'income_max_bdt'            => ['nullable', 'integer', 'min:0'],
            'country'                   => ['nullable', 'array'],
            'country.*'                 => ['string'],

            // ── Location Hierarchy Preferences ────────────────────────────────
            'pref_divisions'            => ['nullable', 'array'],
            'pref_divisions.*'          => ['string'],
            'pref_districts'            => ['nullable', 'array'],
            'pref_districts.*'          => ['string'],
            'pref_provinces'            => ['nullable', 'array'],
            'pref_provinces.*'          => ['string'],
            'pref_states'               => ['nullable', 'array'],
            'pref_states.*'             => ['string'],

            'diet'                      => ['nullable', 'array'],
            'diet.*'                    => ['string', 'in:vegetarian,non_vegetarian,vegan,jain'],
            'smoking_acceptable'        => ['nullable', 'boolean'],
            'drinking_acceptable'       => ['nullable', 'boolean'],

            // ── Physical Appearance ───────────────────────────────────────────
            'body_type'                 => ['nullable', 'array'],
            'body_type.*'               => ['string', 'in:slim,average,athletic,heavy'],
            'complexion'                => ['nullable', 'array'],
            'complexion.*'              => ['string', 'in:very_fair,fair,wheatish,dark'],
            'blood_group'               => ['nullable', 'array'],
            'blood_group.*'             => ['string', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],

            // ── Linguistic / Identity ─────────────────────────────────────────
            'mother_tongue'             => ['nullable', 'array'],
            'mother_tongue.*'           => ['string'],

            // ── Religious / Spiritual ─────────────────────────────────────────
            'manglik_status'            => ['nullable', 'array'],
            'manglik_status.*'          => ['string', 'in:yes,no,partial,dont_know'],
            'rashi'                     => ['nullable', 'array'],
            'rashi.*'                   => ['string', 'max:100'],
            'religiousness'             => ['nullable', 'array'],
            'religiousness.*'           => ['string', 'in:very_religious,religious,moderate,not_religious'],
            'pray'                      => ['nullable', 'array'],
            'pray.*'                    => ['string', 'in:always,usually,sometimes,rarely,never'],

            // ── Family ────────────────────────────────────────────────────────
            'has_children'              => ['nullable', 'string', 'in:no,yes,any'],
            'child_living_status'       => ['nullable', 'array'],
            'child_living_status.*'     => ['string'],
            'family_type'               => ['nullable', 'array'],
            'family_type.*'             => ['string', 'in:nuclear,joint,extended'],
            'family_values'             => ['nullable', 'array'],
            'family_values.*'           => ['string', 'in:traditional,moderate,liberal,religious'],

            // ── Career / Employment ───────────────────────────────────────────
            'working_status'            => ['nullable', 'array'],
            'working_status.*'          => ['string', 'in:working,homemaker,student,not_working'],
            'employed_in'               => ['nullable', 'array'],
            'employed_in.*'             => ['string', 'in:government,private,business,self_employed,not_working'],

            // ── Location / Residency ──────────────────────────────────────────
            'pref_residing_status'      => ['nullable', 'array'],
            'pref_residing_status.*'    => ['string', 'in:citizen,permanent_resident,work_permit,student_visa,visitor_visa,refugee,other'],
        ];
    }
}

