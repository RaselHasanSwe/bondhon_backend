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
        $interest = $event->interest->loadMissing(['sender', 'receiver']);
        $receiver = $interest->receiver;
        $sender   = $interest->sender;

        if (! $receiver || ! $sender) {
            Log::warning('[INTEREST NOTIFICATION] Missing receiver or sender for interest: ' . $interest->id);

            return;
        }

        $sendCount = (int) $interest->send_count;
        Log::info('[INTEREST NOTIFICATION - Send] Sender: ' . $sender->id . ' → Receiver: ' . $receiver->id . ' | Send count: ' . $sendCount);

        $lockKey = 'interest-notification-' . $sender->id . '-' . $receiver->id . '-' . $sendCount;
        $lockTimeout = 10;

        if (! Cache::lock($lockKey, $lockTimeout)->get()) {
            Log::warning('[INTEREST NOTIFICATION - Deduped] Concurrent duplicate detected. Sender: ' . $sender->id . ' → Receiver: ' . $receiver->id);

            return;
        }

        try {
            $this->notificationService->notifyInterestReceived($receiver, $sender);
            SendInterestReceivedEmail::dispatch($interest->id, $sendCount);

            Log::info('[INTEREST NOTIFICATION - Created] Successfully created notification for Interest ID: ' . $interest->id);
        } finally {
            Cache::lock($lockKey, $lockTimeout)->forceRelease();
        }
    }
}
