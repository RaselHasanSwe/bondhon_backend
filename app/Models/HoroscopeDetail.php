<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoroscopeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'birth_place',
        'birth_time',
        'rashi',
        'nakshatra',
        'manglik',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'birth_time' => 'datetime:H:i',
            'manglik' => 'boolean',
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
