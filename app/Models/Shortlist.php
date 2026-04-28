<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shortlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shortlisted_user_id',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [];
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shortlistedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shortlisted_user_id');
    }
}
