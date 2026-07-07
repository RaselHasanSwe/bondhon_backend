<?php

namespace App\Events;

use App\Models\CallLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when the receiver's client shows the incoming-call UI.
 * Tells the caller to switch from "Calling…" to "Ringing…" + ringback tone.
 */
class CallRinging implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly CallLog $callLog) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->callLog->caller_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.ringing';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callLog->id,
        ];
    }
}
