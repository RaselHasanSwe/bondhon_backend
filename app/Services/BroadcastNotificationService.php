<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Support\Facades\Log;

class BroadcastNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send a broadcast notification to all (or plan-filtered) users.
     *
     * @param string      $title   Notification title
     * @param string      $message Notification message
     * @param string      $target  'all' | 'free' | 'silver' | 'gold' | 'platinum'
     * @param string      $channel 'application' | 'email' | 'both'
     * @return int The number of users notified
     */
    public function broadcast(string $title, string $message, string $target = 'all', string $channel = 'application'): int
    {
        $query = User::where('is_banned', false)->whereNotNull('email_verified_at');

        if ($target !== 'all') {
            $query->where('subscription_plan', $target);
        }

        $users = $query->get(['id', 'name', 'email', 'subscription_plan']);

        return $this->sendToUsers($users, $title, $message, $channel);
    }

    /**
     * Send notification to specific users by IDs.
     *
     * @param array  $userIds  Array of user IDs
     * @param string $title
     * @param string $message
     * @param string $channel 'application' | 'email' | 'both'
     * @return int
     */
    public function broadcastToSpecificUsers(array $userIds, string $title, string $message, string $channel = 'application'): int
    {
        $users = User::whereIn('id', $userIds)
            ->where('is_banned', false)
            ->whereNotNull('email_verified_at')
            ->get(['id', 'name', 'email', 'subscription_plan']);

        return $this->sendToUsers($users, $title, $message, $channel);
    }

    /**
     * Core: iterate users and send via the requested channel(s).
     */
    private function sendToUsers($users, string $title, string $message, string $channel): int
    {
        $count = 0;
        foreach ($users as $user) {
            try {
                if ($channel === 'application' || $channel === 'both') {
                    $this->notificationService->send($user, 'broadcast_message', [
                        'title'   => $title,
                        'message' => $message,
                        'icon'    => 'megaphone',
                    ]);
                }
                if ($channel === 'email' || $channel === 'both') {
                    $user->notify(new AdminBroadcastNotification($title, $message));
                }
                $count++;
            } catch (\Throwable $e) {
                Log::error('[BROADCAST] Failed for user ' . $user->id . ': ' . $e->getMessage());
            }
        }

        Log::info('[BROADCAST NOTIFICATION] Sent to ' . $count . ' users. Channel: ' . $channel);

        return $count;
    }
}
