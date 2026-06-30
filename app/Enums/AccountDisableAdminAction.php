<?php

namespace App\Enums;

enum AccountDisableAdminAction: string
{
    case Disabled = 'disabled';
    case Banned = 'banned';
    case Reactivated = 'reactivated';

    public function label(): string
    {
        return match ($this) {
            self::Disabled    => 'Account Disabled',
            self::Banned      => 'Account Banned',
            self::Reactivated => 'Account Reactivated',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
