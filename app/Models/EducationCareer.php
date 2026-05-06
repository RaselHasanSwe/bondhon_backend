<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationCareer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'highest_education',
        'college_university',
        'institution_name_year',
        'employer_name',
        'job_location',
        'designation',
        'experience_years',
        'profession',
        'employed_in',
        'annual_income_bdt',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'annual_income_bdt' => 'integer',
            'experience_years' => 'integer',
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
