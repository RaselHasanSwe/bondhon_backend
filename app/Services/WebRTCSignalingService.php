<?php

namespace App\Services;

use App\Events\WebRTCSignal;
use App\Models\CallLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * WebRTCSignalingService
 *
 * Handles:
 * - Generating time-limited TURN credentials (coturn REST API / HMAC method)
 * - Relaying WebRTC SDP offer/answer and ICE candidates between peers
 *   via Laravel Reverb private channels.
 */
class WebRTCSignalingService
{
    /**
     * Build the ICE server configuration array for the given user.
     *
     * Returns STUN servers + TURN servers with static or HMAC credentials.
     */
    public function getIceServers(User $user): array
    {
        $servers = [];

        // ── STUN (always included) ─────────────────────────────────────
        foreach (config('webrtc.stun_servers', []) as $url) {
            $servers[] = ['urls' => $url];
        }

        // ── TURN (when host + credentials are configured) ────────────────
        $turnConfig = config('webrtc.turn');
        $host       = $turnConfig['host'] ?? '';

        if (! $host) {
            return $servers;
        }

        $credentials = $this->buildTurnCredentials($turnConfig, $user);
        if (! $credentials) {
            return $servers;
        }

        [$username, $password] = $credentials;
        $port    = $turnConfig['port'] ?? 3478;
        $tlsPort = $turnConfig['tls_port'] ?? 5349;

        $servers[] = [
            'urls'       => "turn:{$host}:{$port}?transport=udp",
            'username'   => $username,
            'credential' => $password,
        ];
        $servers[] = [
            'urls'       => "turn:{$host}:{$port}?transport=tcp",
            'username'   => $username,
            'credential' => $password,
        ];

        if ($turnConfig['enable_tls'] ?? false) {
            $servers[] = [
                'urls'       => "turns:{$host}:{$tlsPort}?transport=tcp",
                'username'   => $username,
                'credential' => $password,
            ];
        }

        return $servers;
    }

    /**
     * Resolve TURN username/password for static or HMAC auth.
     *
     * @return array{0: string, 1: string}|null
     */
    private function buildTurnCredentials(array $turnConfig, User $user): ?array
    {
        $authMode = $turnConfig['auth_mode'] ?? 'static';

        if ($authMode === 'hmac') {
            $secret = $turnConfig['secret'] ?? '';
            if (! $secret) {
                return null;
            }

            $ttl      = (int) ($turnConfig['credential_ttl'] ?? 86400);
            $expiry   = time() + $ttl;
            $username = "{$expiry}:{$user->id}";
            $password = base64_encode(hash_hmac('sha1', $username, $secret, true));

            return [$username, $password];
        }

        $username = $turnConfig['username'] ?? '';
        $password = $turnConfig['password'] ?? '';

        if (! $username || ! $password) {
            return null;
        }

        return [$username, $password];
    }

    /**
     * Relay a WebRTC signal (offer / answer / ice-candidate) to the target user
     * via a Reverb private channel event.
     */
    public function relaySignal(CallLog $call, User $sender, int $toUserId, string $type, array $payload): void
    {
        Log::info('[WEBRTC - Relay Signal]', [
            'call_id'    => $call->id,
            'from'       => $sender->id,
            'to'         => $toUserId,
            'type'       => $type,
        ]);

        broadcast(new WebRTCSignal(
            callId:     $call->id,
            fromUserId: $sender->id,
            toUserId:   $toUserId,
            type:       $type,
            payload:    $payload,
        ));
    }
}
