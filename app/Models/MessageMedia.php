<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageMedia extends Model
{
    protected $table = 'message_media';

    protected $fillable = [
        'message_id',
        'file_path',
        'file_name',
        'file_size',
        'file_mime_type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size'  => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}

