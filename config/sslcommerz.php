<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSLCommerz Configuration
    |--------------------------------------------------------------------------
    | Credentials from your SSLCommerz merchant account.
    | Set SSLCZ_IS_LIVE=false for sandbox testing.
    */
    'store_id'    => env('SSLCZ_STORE_ID', ''),
    'store_passwd' => env('SSLCZ_STORE_PASSWD', ''),
    'is_live'     => (bool) env('SSLCZ_IS_LIVE', false),

    // Callback URLs — handled by this backend, then redirect to frontend
    'success_url' => env('APP_URL') . '/payment/success',
    'fail_url'    => env('APP_URL') . '/payment/fail',
    'cancel_url'  => env('APP_URL') . '/payment/cancel',
    'ipn_url'     => env('APP_URL') . '/payment/ipn',
];

