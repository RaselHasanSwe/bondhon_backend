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
        'nick_name',
        'profile_created_for',
        'looking_for',
        'dob',
        'height_cm',
        'weight_kg',
        'body_type',
        'eye_color',
        'hair_color',
        'complexion',
        'blood_group',
        'marital_status',
        'disability',
        'mother_tongue',
        'nationality',
        'country',
        'state',
        'city',
        'postal_code',
        'residing_status',
        'about_me',
        'what_looking_for',
        'profile_completion_percentage',
        'is_verified',
        'is_photo_approved',
        'last_seen_at',
        'privacy_settings',
        'custom_fields',
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
            'custom_fields'    => 'array',
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
