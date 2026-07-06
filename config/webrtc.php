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
    |
    | Auth modes (TURN_AUTH_MODE):
    |   static — fixed username/password (coturn `user=name:password` in turnserver.conf)
    |   hmac   — time-limited credentials via coturn `static-auth-secret` (recommended for prod)
    |
    | Development & production use the same keys; set values in each environment's .env.
    */
    'turn' => [
        'host'           => env('TURN_SERVER_HOST', ''),
        'port'           => (int) env('TURN_SERVER_PORT', 3478),
        'tls_port'       => (int) env('TURN_SERVER_TLS_PORT', 5349),
        'enable_tls'     => env('TURN_ENABLE_TLS', false),
        'auth_mode'      => env('TURN_AUTH_MODE', 'static'), // static | hmac
        'username'       => env('TURN_USERNAME', ''),
        'password'       => env('TURN_PASSWORD', ''),
        'secret'         => env('TURN_SECRET', ''),
        'credential_ttl' => (int) env('TURN_CREDENTIAL_TTL', 86400),
    ],

];

