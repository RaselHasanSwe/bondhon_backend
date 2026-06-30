<?php

return [
    'base_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'verify_email' => env('EMAIL_VERIFICATION_URL', 'http://localhost:3000/verify-email'),
    'revalidate_secret' => env('FRONTEND_REVALIDATE_SECRET'),
];
