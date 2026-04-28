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
        'city',
        'diet',
        'smoking_acceptable',
        'drinking_acceptable',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'age_min' => 'integer',
            'age_max' => 'integer',
            'height_min_cm' => 'integer',
            'height_max_cm' => 'integer',
            'income_min_bdt' => 'integer',
            'income_max_bdt' => 'integer',
            'marital_status' => 'json',
            'religion' => 'json',
            'caste' => 'json',
            'education' => 'json',
            'profession' => 'json',
            'country' => 'json',
            'city' => 'json',
            'diet' => 'json',
            'smoking_acceptable' => 'boolean',
            'drinking_acceptable' => 'boolean',
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
