<?php

namespace App\Services;

use App\Events\InterestReceived;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService — Phase 3
 *
 * Creates in-app (DB) notifications and broadcasts them via Reverb.
 */
class NotificationService
{
    // Notification type constants
    public const TYPE_INTEREST_RECEIVED   = 'interest_received';
    public const TYPE_INTEREST_ACCEPTED   = 'interest_accepted';
    public const TYPE_PROFILE_VIEWED      = 'profile_viewed';
    public const TYPE_NEW_MESSAGE         = 'new_message';
    public const TYPE_MATCH_DIGEST        = 'match_digest';
    public const TYPE_SUBSCRIPTION_EXPIRY = 'subscription_expiry';
    public const TYPE_PHOTO_APPROVED      = 'photo_approved';
    public const TYPE_PHOTO_REJECTED      = 'photo_rejected';
    public const TYPE_FACE_SCAN_APPROVED  = 'face_scan_approved';
    public const TYPE_FACE_SCAN_REJECTED  = 'face_scan_rejected';
    public const TYPE_INTEREST_EXPIRED    = 'interest_expired';
    public const TYPE_SYSTEM              = 'system';
    public const TYPE_BROADCAST_MESSAGE   = 'broadcast_message';

    /**
     * Send a notification to a user (stores in DB + broadcasts via WebSocket).
     */
    public function send(User $user, string $type, array $data): DatabaseNotification
    {
        Log::info('[NOTIFICATION - Send] User: ' . $user->id . ' | Type: ' . $type);

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->create([
            'id'      => \Illuminate\Support\Str::uuid(),
            'type'    => $type,
            'data'    => $data,   // array cast on the Notification model handles serialisation
            'is_read' => false,
        ]);

        // Broadcast to the user's private channel
        $this->broadcastToUser($user->id, $notification);

        return $notification;
    }

    /**
     * Get paginated notifications for a user.
     */
    public function getForUser(User $user, int $perPage = 20)
    {
        return $user->notifications()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        if (!$notification) return false;

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(User $user): int
    {
        return $user->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Delete a notification.
     */
    public function delete(User $user, string $notificationId): bool
    {
        return (bool) $user->notifications()->where('id', $notificationId)->delete();
    }

    /**
     * Get unread count for a user.
     */
    public function unreadCount(User $user): int
    {
        return $user->notifications()->where('is_read', false)->count();
    }

    // -------------------------------------------------------------------------
    // Domain-specific helpers
    // -------------------------------------------------------------------------

    public function notifyInterestReceived(User $receiver, User $sender): void
    {
        $this->send($receiver, self::TYPE_INTEREST_RECEIVED, [
            'title'       => 'New Interest Received',
            'message'     => $sender->name . ' has sent you an interest.',
            'sender_id'   => $sender->id,
            'sender_name' => $sender->name,
            'profile_id'  => $sender->profile?->profile_id,
            'icon'        => 'heart',
        ]);
    }

    public function notifyInterestAccepted(User $sender, User $accepter): void
    {
        $this->send($sender, self::TYPE_INTEREST_ACCEPTED, [
            'title'         => 'Interest Accepted!',
            'message'       => $accepter->name . ' has accepted your interest. You can now chat!',
            'accepter_id'   => $accepter->id,
            'accepter_name' => $accepter->name,
            'profile_id'    => $accepter->profile?->profile_id,
            'icon'          => 'check-heart',
        ]);
    }

    public function notifyProfileViewed(User $viewed, User $viewer): void
    {
        $this->send($viewed, self::TYPE_PROFILE_VIEWED, [
            'title'       => 'Profile Viewed',
            'message'     => $viewer->name . ' viewed your profile.',
            'viewer_id'   => $viewer->id,
            'viewer_name' => $viewer->name,
            'profile_id'  => $viewer->profile?->profile_id,
            'icon'        => 'eye',
        ]);
    }

    public function notifyNewMessage(User $receiver, User $sender, string $preview, int $conversationId): void
    {
        $this->send($receiver, self::TYPE_NEW_MESSAGE, [
            'title'           => 'New Message',
            'message'         => $sender->name . ': ' . $preview,
            'sender_id'       => $sender->id,
            'sender_name'     => $sender->name,
            'conversation_id' => $conversationId,
            'icon'            => 'chat',
        ]);
    }

    public function notifyPhotoApproved(User $user): void
    {
        $this->send($user, self::TYPE_PHOTO_APPROVED, [
            'title'   => 'Photo Approved',
            'message' => 'Your profile photo has been approved and is now visible.',
            'icon'    => 'check',
        ]);
    }

    public function notifyPhotoRejected(User $user, string $reason = ''): void
    {
        $this->send($user, self::TYPE_PHOTO_REJECTED, [
            'title'   => 'Photo Rejected',
            'message' => 'Your profile photo was rejected.' . ($reason ? ' Reason: ' . $reason : ''),
            'reason'  => $reason,
            'icon'    => 'x',
        ]);
    }

    public function notifyFaceScanApproved(User $user): void
    {
        $this->send($user, self::TYPE_FACE_SCAN_APPROVED, [
            'title'   => 'Face Verification Approved',
            'message' => 'Your face scan has been approved. Your account is now fully active.',
            'icon'    => 'check',
        ]);
    }

    public function notifyFaceScanRejected(User $user, string $reason = ''): void
    {
        $this->send($user, self::TYPE_FACE_SCAN_REJECTED, [
            'title'   => 'Face Verification Rejected',
            'message' => 'Your face scan was rejected. Please submit a new scan.' . ($reason ? ' Reason: ' . $reason : ''),
            'reason'  => $reason,
            'icon'    => 'x',
        ]);
    }

    public function notifySubscriptionExpiring(User $user, int $daysLeft): void
    {
        $this->send($user, self::TYPE_SUBSCRIPTION_EXPIRY, [
            'title'     => 'Subscription Expiring Soon',
            'message'   => 'Your ' . $user->subscription_plan . ' plan expires in ' . $daysLeft . ' days. Renew now!',
            'days_left' => $daysLeft,
            'icon'      => 'clock',
        ]);
    }

    public function notifyInterestExpired(User $user, User $otherUser): void
    {
        $this->send($user, self::TYPE_INTEREST_EXPIRED, [
            'title'   => 'Interest Expired',
            'message' => 'Your interest with ' . $otherUser->name . ' has expired.',
            'icon'    => 'clock',
        ]);
    }

    public function notifySystem(User $user, string $title, string $message): void
    {
        $this->send($user, self::TYPE_SYSTEM, [
            'title'   => $title,
            'message' => $message,
            'icon'    => 'megaphone',
        ]);
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function broadcastToUser(int $userId, $notification): void
    {
        try {
            $data = is_string($notification->data)
                ? json_decode($notification->data, true)
                : $notification->data;

            broadcast(new \App\Events\NotificationCreated($userId, [
                'id'         => $notification->id,
                'type'       => $notification->type,
                'data'       => $data,
                'is_read'    => false,
                'read_at'    => null,
                'created_at' => $notification->created_at?->toISOString() ?? now()->toISOString(),
            ]));
        } catch (\Throwable $e) {
            Log::error('[NOTIFICATION - Broadcast] Failed: ' . $e->getMessage());
        }
    }
}
