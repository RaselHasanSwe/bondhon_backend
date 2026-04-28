<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'candidate_id',
        'score',
        'score_breakdown',
        'calculated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'score_breakdown' => 'json',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}
