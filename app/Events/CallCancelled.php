<?php

namespace App\Events;

use App\Models\CallLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Caller cancelled while receiver is still ringing — instant dismiss (no queue). */
class CallCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly CallLog $callLog) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->callLog->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callLog->id,
        ];
    }
}
