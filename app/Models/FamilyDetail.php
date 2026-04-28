<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'family_type',
        'family_status',
        'family_income_bdt_per_month',
        'father_occupation',
        'mother_occupation',
        'brothers_count',
        'sisters_count',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'family_income_bdt_per_month' => 'integer',
            'brothers_count' => 'integer',
            'sisters_count' => 'integer',
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
