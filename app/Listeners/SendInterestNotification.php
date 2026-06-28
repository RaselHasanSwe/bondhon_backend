<?php

namespace App\Listeners;

use App\Events\InterestReceived;
use App\Jobs\SendInterestReceivedEmail;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendInterestNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(InterestReceived $event): void
    {
        $interest = $event->interest;
        $receiver = $interest->receiver;
        $sender   = $interest->sender;

        if (!$receiver || !$sender) {
            Log::warning('[INTEREST NOTIFICATION] Missing receiver or sender for interest: ' . $interest->id);
            return;
        }

        Log::info('[INTEREST NOTIFICATION - Send] Sender: ' . $sender->id . ' → Receiver: ' . $receiver->id);

        // Use a lock to prevent concurrent duplicate notifications
        // Lock key ensures only one notification is created per sender-receiver pair at a time
        $lockKey = 'interest-notification-' . $sender->id . '-' . $receiver->id;
        $lockTimeout = 10; // 10 seconds should be enough for the notification creation

        if (!Cache::lock($lockKey, $lockTimeout)->get()) {
            Log::warning('[INTEREST NOTIFICATION - Deduped] Concurrent duplicate detected. Sender: ' . $sender->id . ' → Receiver: ' . $receiver->id);
            return;
        }

        try {
            // Double-check: verify no notification exists after acquiring lock
            $existingNotifications = $receiver->notifications()
                ->where('type', 'interest_received')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->get();

            $isDuplicate = false;
            foreach ($existingNotifications as $notif) {
                $data = is_array($notif->data) ? $notif->data : json_decode($notif->data, true);
                if (isset($data['sender_id']) && $data['sender_id'] == $sender->id) {
                    $isDuplicate = true;
                    break;
                }
            }

            if ($isDuplicate) {
                Log::warning('[INTEREST NOTIFICATION - Duplicate] Skipping duplicate notification for Interest ID: ' . $interest->id . ' from Sender: ' . $sender->id);
                return;
            }

            // Send in-app notification via NotificationService
            $this->notificationService->notifyInterestReceived($receiver, $sender);

            // Dispatch delayed interest-received email (see config/notifications.php)
            SendInterestReceivedEmail::dispatch($interest->id);

            Log::info('[INTEREST NOTIFICATION - Created] Successfully created notification for Interest ID: ' . $interest->id);
        } finally {
            // Always release the lock
            Cache::lock($lockKey, $lockTimeout)->forceRelease();
        }
    }
}
