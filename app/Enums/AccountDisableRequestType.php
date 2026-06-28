<?php

namespace App\Enums;

enum AccountDisableRequestType: string
{
    case PersonalReason = 'personal_reason';
    case GotMarriedThroughPlatform = 'got_married_through_platform';

    public function label(): string
    {
        return match ($this) {
            self::PersonalReason => 'Personal Reason',
            self::GotMarriedThroughPlatform => 'Got Married Through This Platform',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
