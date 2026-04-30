<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
        'last_message_id',
        'user_one_unread',
        'user_two_unread',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'last_message_at'  => 'datetime',
            'user_one_unread'  => 'integer',
            'user_two_unread'  => 'integer',
        ];
    }

    /**
     * Get the other participant for a given user.
     */
    public function getOtherUser(int $userId): User
    {
        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }

    /**
     * Get unread count for a specific user.
     */
    public function getUnreadCountForUser(int $userId): int
    {
        if ($this->user_one_id === $userId) {
            return (int) ($this->user_one_unread ?? 0);
        }
        return (int) ($this->user_two_unread ?? 0);
    }

    /**
     * Increment unread for a specific user.
     */
    public function incrementUnreadFor(int $userId): void
    {
        if ($this->user_one_id === $userId) {
            $this->increment('user_one_unread');
        } else {
            $this->increment('user_two_unread');
        }
    }

    /**
     * Reset unread for a specific user.
     */
    public function resetUnreadFor(int $userId): void
    {
        if ($this->user_one_id === $userId) {
            $this->update(['user_one_unread' => 0]);
        } else {
            $this->update(['user_two_unread' => 0]);
        }
    }

    /**
     * Relationships
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }
}
