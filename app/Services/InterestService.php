<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Interest;
use App\Models\User;
use Illuminate\Support\Collection;

class InterestService
{
    /**
     * Resends allowed after decline/ignore (initial send is always 1 attempt).
     * Total sends = 1 + maxResendAttempts().
     */
    public function maxResendAttempts(): int
    {
        return max(0, (int) config('notifications.max_interest_resend_attempts', 4));
    }

    public function maxSendAttempts(): int
    {
        return 1 + $this->maxResendAttempts();
    }

    public function sendCount(Interest $interest): int
    {
        $count = $interest->send_count;

        return $count === null ? 1 : max(1, (int) $count);
    }

    public function remainingSendAttempts(Interest $interest): int
    {
        return max(0, $this->maxSendAttempts() - $this->sendCount($interest));
    }

    public function canSenderResend(Interest $interest): bool
    {
        return $this->sendCount($interest) < $this->maxSendAttempts();
    }

    public function findInterestBetween(int $userId, int $otherUserId): ?Interest
    {
        $outgoing = $this->findOutgoing($userId, $otherUserId);
        $incoming = $this->findIncoming($userId, $otherUserId);

        if ($outgoing && in_array($outgoing->status, ['pending', 'accepted'], true)) {
            return $outgoing;
        }

        if ($incoming && in_array($incoming->status, ['pending', 'accepted'], true)) {
            return $incoming;
        }

        if ($outgoing && $outgoing->status === 'accepted') {
            return $outgoing;
        }

        if ($incoming && $incoming->status === 'accepted') {
            return $incoming;
        }

        return $outgoing ?? $incoming;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveConnection(User $authUser, ?Interest $interest): array
    {
        if (! $interest) {
            return $this->emptyConnectionMeta();
        }

        $otherUserId = $interest->sender_id === $authUser->id
            ? $interest->receiver_id
            : $interest->sender_id;

        return $this->resolvePair(
            $authUser,
            $this->findOutgoing($authUser->id, $otherUserId),
            $this->findIncoming($authUser->id, $otherUserId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvePair(User $authUser, ?Interest $outgoing, ?Interest $incoming): array
    {
        if ($outgoing?->status === 'accepted' || $incoming?->status === 'accepted') {
            $accepted = $outgoing?->status === 'accepted' ? $outgoing : $incoming;

            return [
                'connection_status'       => 'accepted',
                'interest_id'             => $accepted->id,
                'is_interest_sender'      => $accepted->sender_id === $authUser->id,
                'conversation_id'         => null,
                'send_count'              => $this->sendCount($accepted),
                'can_send_interest'       => false,
                'remaining_send_attempts' => 0,
            ];
        }

        if ($incoming?->status === 'pending') {
            return [
                'connection_status'       => 'pending',
                'interest_id'             => $incoming->id,
                'is_interest_sender'      => false,
                'conversation_id'         => null,
                'send_count'              => $this->sendCount($incoming),
                'can_send_interest'       => false,
                'remaining_send_attempts' => 0,
            ];
        }

        if ($outgoing?->status === 'pending') {
            return [
                'connection_status'       => 'pending',
                'interest_id'             => $outgoing->id,
                'is_interest_sender'      => true,
                'conversation_id'         => null,
                'send_count'              => $this->sendCount($outgoing),
                'can_send_interest'       => false,
                'remaining_send_attempts' => $this->remainingSendAttempts($outgoing),
            ];
        }

        if ($outgoing && in_array($outgoing->status, ['declined', 'ignored', 'expired'], true)) {
            if (! $this->canSenderResend($outgoing)) {
                $terminalStatus = $outgoing->status === 'expired' ? 'declined' : $outgoing->status;

                return [
                    'connection_status'       => $terminalStatus,
                    'interest_id'             => $outgoing->id,
                    'is_interest_sender'      => true,
                    'conversation_id'         => null,
                    'send_count'              => $this->sendCount($outgoing),
                    'can_send_interest'       => false,
                    'remaining_send_attempts' => 0,
                ];
            }

            return [
                'connection_status'       => 'none',
                'interest_id'             => $outgoing->id,
                'is_interest_sender'      => true,
                'conversation_id'         => null,
                'send_count'              => $this->sendCount($outgoing),
                'can_send_interest'       => true,
                'remaining_send_attempts'   => $this->remainingSendAttempts($outgoing),
            ];
        }

        return $this->emptyConnectionMeta();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStatusPayloadForUser(User $authUser, int $otherUserId): array
    {
        $meta = $this->resolvePair(
            $authUser,
            $this->findOutgoing($authUser->id, $otherUserId),
            $this->findIncoming($authUser->id, $otherUserId),
        );

        return [
            'status'                  => $meta['connection_status'],
            'interest_id'             => $meta['interest_id'],
            'is_sender'               => $meta['is_interest_sender'],
            'send_count'              => $meta['send_count'],
            'can_send_interest'       => $meta['can_send_interest'],
            'remaining_send_attempts' => $meta['remaining_send_attempts'],
        ];
    }

    /**
     * @param  Collection<int, int>  $otherUserIds
     * @return Collection<int|string, array<string, mixed>>
     */
    public function mapConnectionMetaForUsers(User $user, Collection $otherUserIds): Collection
    {
        return $otherUserIds->mapWithKeys(function ($otherUserId) use ($user) {
            return [$otherUserId => $this->resolvePair(
                $user,
                $this->findOutgoing($user->id, $otherUserId),
                $this->findIncoming($user->id, $otherUserId),
            )];
        });
    }

    /**
     * @param  iterable<mixed>  $items
     */
    public function attachConnectionMetaToItems(User $user, iterable $items, callable $otherUserIdResolver): void
    {
        $collection = collect($items);
        if ($collection->isEmpty()) {
            return;
        }

        $otherUserIds = $collection
            ->map(fn ($item) => $otherUserIdResolver($item))
            ->unique()
            ->values();

        $metaMap = $this->mapConnectionMetaForUsers($user, $otherUserIds);

        $acceptedUserIds = $metaMap
            ->filter(fn (array $meta) => $meta['connection_status'] === 'accepted')
            ->keys();

        $conversationMap = $this->mapConversations($user, $acceptedUserIds);

        $collection->each(function ($item) use ($metaMap, $conversationMap, $otherUserIdResolver) {
            $otherUserId = $otherUserIdResolver($item);
            $meta = $metaMap->get($otherUserId) ?? $this->emptyConnectionMeta();

            if ($meta['connection_status'] === 'accepted') {
                $meta['conversation_id'] = $conversationMap->get($otherUserId)?->id;
            }

            foreach ($meta as $key => $value) {
                $item->setAttribute($key, $value);
            }
        });
    }

    /**
     * @param  Collection<int|string, int>  $acceptedUserIds
     * @return Collection<int|string, Conversation>
     */
    private function mapConversations(User $user, Collection $acceptedUserIds): Collection
    {
        if ($acceptedUserIds->isEmpty()) {
            return collect();
        }

        return Conversation::query()
            ->where(function ($q) use ($user, $acceptedUserIds) {
                foreach ($acceptedUserIds as $otherUserId) {
                    [$userOneId, $userTwoId] = $user->id < $otherUserId
                        ? [$user->id, $otherUserId]
                        : [$otherUserId, $user->id];

                    $q->orWhere(function ($pair) use ($userOneId, $userTwoId) {
                        $pair->where('user_one_id', $userOneId)
                            ->where('user_two_id', $userTwoId);
                    });
                }
            })
            ->get()
            ->keyBy(function (Conversation $conversation) use ($user) {
                return $conversation->user_one_id === $user->id
                    ? $conversation->user_two_id
                    : $conversation->user_one_id;
            });
    }

    private function findOutgoing(int $userId, int $otherUserId): ?Interest
    {
        return Interest::query()
            ->where('sender_id', $userId)
            ->where('receiver_id', $otherUserId)
            ->first();
    }

    private function findIncoming(int $userId, int $otherUserId): ?Interest
    {
        return Interest::query()
            ->where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyConnectionMeta(): array
    {
        return [
            'connection_status'       => 'none',
            'interest_id'             => null,
            'is_interest_sender'      => false,
            'conversation_id'         => null,
            'send_count'              => 0,
            'can_send_interest'       => true,
            'remaining_send_attempts' => $this->maxSendAttempts(),
        ];
    }
}
