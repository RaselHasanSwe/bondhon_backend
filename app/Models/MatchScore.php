<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'candidate_id',
        'score',
        'score_breakdown',
        'calculated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'score_breakdown' => 'json',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /**
     * Normalize a user pair so user_id is always the lower ID.
     *
     * @return array{0: int, 1: int}
     */
    public static function normalizePairIds(int $userIdA, int $userIdB): array
    {
        return $userIdA < $userIdB ? [$userIdA, $userIdB] : [$userIdB, $userIdA];
    }

    public static function pairKey(int $userIdA, int $userIdB): string
    {
        [$low, $high] = self::normalizePairIds($userIdA, $userIdB);

        return $low . ':' . $high;
    }

    public static function findForPair(int $userIdA, int $userIdB): ?self
    {
        [$low, $high] = self::normalizePairIds($userIdA, $userIdB);

        return self::query()
            ->where('user_id', $low)
            ->where('candidate_id', $high)
            ->first();
    }

    /**
     * The other user in this pair for a given viewer.
     */
    public function partnerFor(int $viewerUserId): ?User
    {
        if ($this->user_id === $viewerUserId) {
            return $this->relationLoaded('candidate') ? $this->candidate : $this->candidate()->first();
        }

        if ($this->candidate_id === $viewerUserId) {
            return $this->relationLoaded('user') ? $this->user : $this->user()->first();
        }

        return null;
    }

    public function scopeInvolvingUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('candidate_id', $userId);
        });
    }
}
