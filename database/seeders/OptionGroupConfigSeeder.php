<?php

namespace Database\Seeders;

use App\Models\OptionGroupConfig;
use Illuminate\Database\Seeder;

class OptionGroupConfigSeeder extends Seeder
{
    public function run(): void
    {
        OptionGroupConfig::truncate();

        $n = 0;
        $g = fn(array $d) => OptionGroupConfig::create(array_merge(['is_system' => true, 'sort_order' => ++$n], $d));

        // ── Basic Info Tab ─────────────────────────────────────────────
        $g(['group_key'=>'profile_created_by',  'label'=>'Profile Created By',  'profile_tab'=>'basic',    'field_name'=>'profile_created_by',  'input_type'=>'select']);
        $g(['group_key'=>'profile_created_for', 'label'=>'Profile Created For', 'profile_tab'=>'basic',    'field_name'=>'profile_created_for', 'input_type'=>'select']);
        $g(['group_key'=>'looking_for',         'label'=>'Looking For',         'profile_tab'=>'basic',    'field_name'=>'looking_for',         'input_type'=>'select']);
        $g(['group_key'=>'marital_status',      'label'=>'Marital Status',       'profile_tab'=>'basic',    'field_name'=>'marital_status',      'input_type'=>'select']);
        $g(['group_key'=>'have_children',       'label'=>'Have Children',        'profile_tab'=>'basic',    'field_name'=>'have_children',       'input_type'=>'select']);
        $g(['group_key'=>'child_living_status', 'label'=>'Child Living Status',  'profile_tab'=>'basic',    'field_name'=>'child_living_status', 'input_type'=>'select']);
        $g(['group_key'=>'body_type',           'label'=>'Body Type',            'profile_tab'=>'basic',    'field_name'=>'body_type',           'input_type'=>'select']);
        $g(['group_key'=>'eye_color',           'label'=>'Eye Color',            'profile_tab'=>'basic',    'field_name'=>'eye_color',           'input_type'=>'select']);
        $g(['group_key'=>'hair_color',          'label'=>'Hair Color',           'profile_tab'=>'basic',    'field_name'=>'hair_color',          'input_type'=>'select']);
        $g(['group_key'=>'complexion',          'label'=>'Complexion',           'profile_tab'=>'basic',    'field_name'=>'complexion',          'input_type'=>'select']);
        $g(['group_key'=>'blood_group',         'label'=>'Blood Group',          'profile_tab'=>'basic',    'field_name'=>'blood_group',         'input_type'=>'select']);
        $g(['group_key'=>'disability',          'label'=>'Disability',           'profile_tab'=>'basic',    'field_name'=>'disability',          'input_type'=>'select']);
        $g(['group_key'=>'mother_tongue',       'label'=>'Mother Tongue',        'profile_tab'=>'basic',    'field_name'=>'mother_tongue',       'input_type'=>'select']);

        // ── Location Tab ───────────────────────────────────────────────
        $g(['group_key'=>'nationality',     'label'=>'Nationality',         'profile_tab'=>'location', 'field_name'=>'nationality',     'input_type'=>'select']);
        $g(['group_key'=>'country',         'label'=>'Country',             'profile_tab'=>'location', 'field_name'=>'country',         'input_type'=>'select',       'max_nesting_depth'=>3]);
        $g(['group_key'=>'bd_division',     'label'=>'BD Division',         'profile_tab'=>'location', 'field_name'=>'city',            'input_type'=>'select',       'parent_group_key'=>'country',     'max_nesting_depth'=>2]);
        $g(['group_key'=>'bd_district',     'label'=>'BD District',         'profile_tab'=>'location', 'field_name'=>'state',           'input_type'=>'select',       'parent_group_key'=>'bd_division', 'max_nesting_depth'=>1]);
        $g(['group_key'=>'residing_status', 'label'=>'Residing Status',     'profile_tab'=>'location', 'field_name'=>'residing_status', 'input_type'=>'select']);

        // ── Religion Tab ───────────────────────────────────────────────
        $g(['group_key'=>'religion',       'label'=>'Religion',       'profile_tab'=>'religion', 'field_name'=>'religion',       'input_type'=>'select', 'max_nesting_depth'=>3]);
        $g(['group_key'=>'caste',          'label'=>'Caste',          'profile_tab'=>'religion', 'field_name'=>'caste',          'input_type'=>'select', 'parent_group_key'=>'religion', 'max_nesting_depth'=>2]);
        $g(['group_key'=>'religiousness',  'label'=>'Religiousness',  'profile_tab'=>'religion', 'field_name'=>'religiousness',  'input_type'=>'select']);
        $g(['group_key'=>'pray',           'label'=>'Prayer Frequency','profile_tab'=>'religion', 'field_name'=>'pray',           'input_type'=>'select']);
        $g(['group_key'=>'manglik_status', 'label'=>'Manglik Status', 'profile_tab'=>'religion', 'field_name'=>'manglik_status', 'input_type'=>'select']);

        // ── Career Tab ─────────────────────────────────────────────────
        $g(['group_key'=>'education_level', 'label'=>'Education Level', 'profile_tab'=>'career', 'field_name'=>'highest_education', 'input_type'=>'select']);
        $g(['group_key'=>'profession',      'label'=>'Profession',      'profile_tab'=>'career', 'field_name'=>'profession',        'input_type'=>'select']);
        $g(['group_key'=>'employed_in',     'label'=>'Employed In',     'profile_tab'=>'career', 'field_name'=>'employed_in',       'input_type'=>'select']);
        $g(['group_key'=>'occupation',      'label'=>'Occupation',      'profile_tab'=>'career', 'field_name'=>null,                'input_type'=>'select']);

        // ── Lifestyle Tab ──────────────────────────────────────────────
        $g(['group_key'=>'diet',     'label'=>'Diet',     'profile_tab'=>'lifestyle', 'field_name'=>'diet',     'input_type'=>'select']);
        $g(['group_key'=>'smoking',  'label'=>'Smoking',  'profile_tab'=>'lifestyle', 'field_name'=>'smoking',  'input_type'=>'select']);
        $g(['group_key'=>'drinking', 'label'=>'Drinking', 'profile_tab'=>'lifestyle', 'field_name'=>'drinking', 'input_type'=>'select']);
        $g(['group_key'=>'eye_wear', 'label'=>'Eye-Wear', 'profile_tab'=>'lifestyle', 'field_name'=>'eye_wear', 'input_type'=>'select']);
        $g(['group_key'=>'hobbies',  'label'=>'Hobbies',  'profile_tab'=>'lifestyle', 'field_name'=>'hobbies',  'input_type'=>'multi_select']);

        // ── Family Tab ─────────────────────────────────────────────────
        $g(['group_key'=>'family_type',   'label'=>'Family Type',   'profile_tab'=>'family', 'field_name'=>'family_type',   'input_type'=>'select']);
        $g(['group_key'=>'family_status', 'label'=>'Family Status', 'profile_tab'=>'family', 'field_name'=>'family_status', 'input_type'=>'select']);
        $g(['group_key'=>'family_values', 'label'=>'Family Values', 'profile_tab'=>'family', 'field_name'=>'family_values', 'input_type'=>'select']);

        // ── Horoscope Tab ──────────────────────────────────────────────
        $g(['group_key'=>'rashi', 'label'=>'Rashi / Zodiac', 'profile_tab'=>'horoscope', 'field_name'=>'rashi', 'input_type'=>'select']);

        // ── Preferences Tab ────────────────────────────────────────────
        $g(['group_key'=>'pref_has_children', 'label'=>'Preference: Has Children', 'profile_tab'=>'preferences', 'field_name'=>'pref_has_children', 'input_type'=>'select']);
        $g(['group_key'=>'working_status',    'label'=>'Working Status',           'profile_tab'=>'preferences', 'field_name'=>'working_status',    'input_type'=>'select']);
    }
}

