<?php

namespace App\Events;

use App\Models\CallLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallAnswered implements ShouldBroadcast
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
        return 'call.answered';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callLog->id,
            'type'    => $this->callLog->type,
        ];
    }
}
