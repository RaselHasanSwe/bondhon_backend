<?php

namespace App\Listeners;

use App\Events\InterestReceived;
use App\Jobs\SendEmailNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
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

        // Send in-app notification via NotificationService
        $this->notificationService->notifyInterestReceived($receiver, $sender);

        // Dispatch email notification job (queued)
        SendEmailNotification::dispatch(
            $receiver,
            'interest_received',
            [
                'sender_name'    => $sender->name,
                'sender_profile' => $sender->profile?->profile_id,
            ]
        );
    }
}
