<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Profile extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'user_id',
        'profile_id',
        'dob',
        'height_cm',
        'weight_kg',
        'complexion',
        'blood_group',
        'marital_status',
        'mother_tongue',
        'nationality',
        'country',
        'state',
        'city',
        'about_me',
        'profile_completion_percentage',
        'is_verified',
        'is_photo_approved',
        'last_seen_at',
        'privacy_settings',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'last_seen_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_photo_approved' => 'boolean',
            'privacy_settings' => 'json',
            'profile_completion_percentage' => 'integer',
            'height_cm' => 'integer',
            'weight_kg' => 'integer',
        ];
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Laravel Scout — Searchable
     */
    public function searchableAs(): string
    {
        return 'profiles';
    }

    public function toSearchableArray(): array
    {
        return [
            'id'       => $this->id,
            'user_id'  => $this->user_id,
            'about_me' => $this->about_me,
            'city'     => $this->city,
            'state'    => $this->state,
            'country'  => $this->country,
        ];
    }
}
