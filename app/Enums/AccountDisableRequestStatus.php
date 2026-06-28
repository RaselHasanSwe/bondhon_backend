<?php

namespace App\Enums;

enum AccountDisableRequestStatus: string
{
    case Pending = 'pending';
    case ActionTaken = 'action_taken';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::ActionTaken => 'Action Taken',
            self::Dismissed => 'Dismissed',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
