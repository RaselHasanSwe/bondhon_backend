<?php

namespace App\Enums;

enum SubscriptionEmailType: string
{
    case PaymentSuccess = 'payment_success';

    public function subjectSuffix(): string
    {
        return match ($this) {
            self::PaymentSuccess => 'Payment Confirmed',
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::PaymentSuccess => 'emails.subscription-payment-success',
        };
    }
}
