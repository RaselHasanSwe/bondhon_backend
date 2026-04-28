<?php

namespace App\Events;

use App\Models\Interest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InterestReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Interest $interest) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->interest->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'interest.received';
    }
}

