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

];
