<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'age_min',
        'age_max',
        'height_min_cm',
        'height_max_cm',
        'marital_status',
        'religion',
        'caste',
        'education',
        'profession',
        'income_min_bdt',
        'income_max_bdt',
        'country',
        // Location hierarchy preferences
        'pref_divisions',
        'pref_districts',
        'pref_provinces',
        'pref_states',
        'diet',
        'smoking_acceptable',
        'drinking_acceptable',
        // Extended preference fields
        'body_type',
        'complexion',
        'blood_group',
        'mother_tongue',
        'manglik_status',
        'rashi',
        'religiousness',
        'pray',
        'has_children',
        'child_living_status',
        'family_type',
        'family_values',
        'working_status',
        'employed_in',
        'pref_residing_status',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'age_min'             => 'integer',
            'age_max'             => 'integer',
            'height_min_cm'       => 'integer',
            'height_max_cm'       => 'integer',
            'income_min_bdt'      => 'integer',
            'income_max_bdt'      => 'integer',
            'marital_status'      => 'json',
            'religion'            => 'json',
            'caste'               => 'json',
            'education'           => 'json',
            'profession'          => 'json',
            'country'             => 'json',
            'pref_divisions'      => 'json',
            'pref_districts'      => 'json',
            'pref_provinces'      => 'json',
            'pref_states'         => 'json',
            'diet'                => 'json',
            'smoking_acceptable'  => 'boolean',
            'drinking_acceptable' => 'boolean',
            // Extended
            'body_type'           => 'json',
            'complexion'          => 'json',
            'blood_group'         => 'json',
            'mother_tongue'       => 'json',
            'manglik_status'      => 'json',
            'rashi'               => 'json',
            'religiousness'       => 'json',
            'pray'                => 'json',
            'child_living_status' => 'json',
            'family_type'         => 'json',
            'family_values'       => 'json',
            'working_status'      => 'json',
            'employed_in'         => 'json',
            'pref_residing_status'=> 'json',
        ];
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
