<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lifestyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'diet',
        'smoking',
        'drinking',
        'eye_wear',
        'hobbies',
        'languages_known',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'hobbies' => 'json',
            'languages_known' => 'json',
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
