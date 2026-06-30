<?php

namespace App\Events;

use App\Models\CallLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcast
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
        return 'call.initiated';
    }

    public function broadcastWith(): array
    {
        $caller = $this->callLog->caller;
        $photo  = $caller?->photos()->where('is_primary', true)->where('is_approved', true)->first();

        return [
            'call_id'     => $this->callLog->id,
            'type'        => $this->callLog->type,
            'caller' => [
                'id'         => $caller?->id,
                'name'       => $caller?->name,
                'avatar'     => $photo?->file_path,
                'profile_id' => $caller?->profile?->profile_id,
            ],
        ];
    }
}
