<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceScanCapture extends Model
{

    protected $fillable = [
        'face_scan_session_id',
        'user_id',
        'capture_key',
        'image_path',
        'metadata',
        'captured_at',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(FaceScanSession::class, 'face_scan_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

