<?php

/*
|--------------------------------------------------------------------------
| WebRTC / ICE Server Configuration — Enorsia Matrimony Platform
|--------------------------------------------------------------------------
|
| Configuration for STUN/TURN servers used in WebRTC peer-to-peer calls.
| TURN credentials are generated as time-limited HMAC tokens per
| the coturn REST API spec (RFC 5766), giving each call a unique
| credential that expires after `credential_ttl` seconds.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | STUN Servers (free public)
    |----------------------------------------------------------------------
    */
    'stun_servers' => [
        'stun:stun.l.google.com:19302',
        'stun:stun1.l.google.com:19302',
        'stun:stun2.l.google.com:19302',
    ],

    /*
    |----------------------------------------------------------------------
    | TURN Server (self-hosted coturn on VPS)
    |----------------------------------------------------------------------
    | Set TURN_SERVER_HOST to your VPS IP or domain.
    | TURN_SECRET must match the `static-auth-secret` in coturn config.
    | TURN_TLS_PORT is for turns: (TLS) connections.
    */
    'turn' => [
        'host'         => env('TURN_SERVER_HOST', ''),          // e.g. 103.12.45.67 or turn.example.com
        'port'         => env('TURN_SERVER_PORT', 3478),        // UDP/TCP port
        'tls_port'     => env('TURN_SERVER_TLS_PORT', 5349),    // TLS port
        'secret'       => env('TURN_SECRET', ''),               // coturn static-auth-secret
        'credential_ttl' => (int) env('TURN_CREDENTIAL_TTL', 86400), // 24 hours
    ],

];

