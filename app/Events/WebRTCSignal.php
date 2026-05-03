<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * WebRTCSignal — relays SDP offer/answer and ICE candidates between peers.
 *
 * Broadcast on the private channel of the target user.
 * Payload type can be: 'offer' | 'answer' | 'ice-candidate'
 */
class WebRTCSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $callId,
        public readonly int    $fromUserId,
        public readonly int    $toUserId,
        public readonly string $type,    // offer | answer | ice-candidate
        public readonly array  $payload, // sdp or candidate data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->toUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'webrtc.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id'      => $this->callId,
            'from_user_id' => $this->fromUserId,
            'type'         => $this->type,
            'payload'      => $this->payload,
        ];
    }
}

