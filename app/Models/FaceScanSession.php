<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FaceScanSession extends Model
{

    protected $fillable = [
        'user_id',
        'status',
        'reviewed_by',
        'review_note',
        'completed_at',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function captures(): HasMany
    {
        return $this->hasMany(FaceScanCapture::class);
    }

    public function latestCapture(): HasOne
    {
        return $this->hasOne(FaceScanCapture::class)->latestOfMany();
    }
}

