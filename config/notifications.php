<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interest Received Email Delay
    |--------------------------------------------------------------------------
    |
    | Seconds to wait before sending the interest-received email after an
    | interest is created. Adjust via INTEREST_RECEIVED_EMAIL_DELAY_SECONDS.
    |
    */

    'interest_received_email_delay_seconds' => (int) env('INTEREST_RECEIVED_EMAIL_DELAY_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Profile Viewed Email Delay
    |--------------------------------------------------------------------------
    |
    | Seconds to wait before sending the profile-viewed email after a view is
    | recorded. Adjust via PROFILE_VIEWED_EMAIL_DELAY_SECONDS.
    |
    */

    'profile_viewed_email_delay_seconds' => (int) env('PROFILE_VIEWED_EMAIL_DELAY_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Max Interest Resend Attempts Per User Pair
    |--------------------------------------------------------------------------
    |
    | After a receiver declines or ignores, the sender may resend this many
    | times (initial send is always allowed once). Total sends = 1 + this value.
    | Adjust via MAX_INTEREST_RESEND_ATTEMPTS.
    |
    */

    'max_interest_resend_attempts' => (int) env('MAX_INTEREST_RESEND_ATTEMPTS', 4),

];
