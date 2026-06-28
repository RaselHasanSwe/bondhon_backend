<?php

namespace App\Enums;

enum AdminAccountActionEmailType: string
{
    case Disabled = 'disabled';
    case Banned = 'banned';
    case Reactivated = 'reactivated';

    public function subjectSuffix(): string
    {
        return match ($this) {
            self::Disabled    => 'Your Account Has Been Disabled',
            self::Banned      => 'Your Account Has Been Suspended',
            self::Reactivated => 'Your Account Has Been Reactivated',
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::Disabled    => 'emails.admin-account-disabled',
            self::Banned      => 'emails.admin-account-banned',
            self::Reactivated => 'emails.admin-account-reactivated',
        };
    }
}
