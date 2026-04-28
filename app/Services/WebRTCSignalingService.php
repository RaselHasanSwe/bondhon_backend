<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * WebRTCSignalingService — Phase 4
 *
 * Handles WebRTC signaling relay via Laravel Reverb WebSocket channels.
 */
class WebRTCSignalingService
{
    /**
     * TODO: Phase 4 — Relay offer/answer SDP to peer.
     */
    public function relaySignal(CallLog $call, User $target, string $type, array $payload): void
    {
        Log::info('[WEBRTC - Relay Signal] Phase 4 not yet implemented. Type: ' . $type);
    }
}

