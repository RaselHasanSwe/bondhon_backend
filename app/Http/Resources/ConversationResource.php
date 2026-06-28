<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * @param int $currentUserId Injected via ->additional([]) or set before transform
     */
    public int $currentUserId = 0;

    public function toArray(Request $request): array
    {
        $currentUserId = $this->currentUserId ?: $request->user()?->id;
        $other = $this->resource->getOtherUser($currentUserId);

        $profile  = $other->profile;
        $primaryPhoto = $other->photos()
            ->where('is_approved', true)
            ->where('is_primary', true)
            ->first();

        $lastSeen = $profile?->last_seen_at;
        $isOnline = $lastSeen && $lastSeen->diffInMinutes(now()) <= 5;

        // Determine privacy for photo
        $privacySettings = $profile?->privacy_settings ?? [];
        $showPhotoTo = $privacySettings['show_photo_to'] ?? 'all';

        $avatarUrl = null;
        if ($showPhotoTo === 'all' && $primaryPhoto) {
            $avatarUrl = $primaryPhoto->file_path;
        }

        return [
            'id'              => $this->id,
            'participant'     => [
                'id'                => $other->id,
                'name'              => $other->name,
                'avatar'            => $avatarUrl,
                'is_online'         => $isOnline,
                'last_seen_at'      => $lastSeen?->toISOString(),
                'profile_id'        => $profile?->profile_id,
                'subscription_plan' => $other->subscription_plan,
            ],
            'last_message'    => $this->whenLoaded('lastMessage', fn () =>
                $this->lastMessage ? new MessageResource($this->lastMessage) : null
            ),
            'last_message_at' => $this->last_message_at?->toISOString(),
            'unread_count'    => $this->getUnreadCountForUser($currentUserId),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}

