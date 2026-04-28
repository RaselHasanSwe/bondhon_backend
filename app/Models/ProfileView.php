<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'viewer_id',
        'viewed_id',
        'viewed_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    public function viewed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewed_id');
    }
}
