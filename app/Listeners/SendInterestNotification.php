<?php

namespace App\Listeners;

use App\Events\InterestReceived;
use App\Jobs\SendEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendInterestNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(InterestReceived $event): void
    {
        $interest = $event->interest;
        $receiver = $interest->receiver;
        $sender   = $interest->sender;

        Log::info('[INTEREST NOTIFICATION - Send] Sender: ' . $sender->id . ' → Receiver: ' . $receiver->id);

        // Dispatch email notification job
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

