<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'body',
        'file_path',
        'file_name',
        'file_size',
        'file_mime_type',
        'duration_seconds',
        'thumbnail_path',
        'reactions',
        'reply_to_message_id',
        'is_deleted',
        'delivered_at',
        'read_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'is_deleted'       => 'boolean',
            'delivered_at'     => 'datetime',
            'read_at'          => 'datetime',
            'reactions'        => 'array',
            'file_size'        => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * Determine the status of the message
     */
    public function getStatusAttribute(): string
    {
        if ($this->read_at) return 'read';
        if ($this->delivered_at) return 'delivered';
        return 'sent';
    }

    /**
     * Relationships
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    /**
     * Scopes
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }
}
