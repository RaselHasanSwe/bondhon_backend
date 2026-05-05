<?php

namespace App\Services;

use App\Models\User;
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
     * @return int The number of users notified
     */
    public function broadcast(string $title, string $message, string $target = 'all'): int
    {
        $query = User::where('is_banned', false)->whereNotNull('email_verified_at');

        if ($target !== 'all') {
            $query->where('subscription_plan', $target);
        }

        $users = $query->get(['id', 'name', 'email', 'subscription_plan']);
        $count = 0;

        foreach ($users as $user) {
            $this->notificationService->send($user, 'broadcast_message', [
                'title'   => $title,
                'message' => $message,
                'icon'    => 'megaphone',
            ]);
            $count++;
        }

        Log::info('[BROADCAST NOTIFICATION] Sent to ' . $count . ' users. Target: ' . $target);

        return $count;
    }
}

