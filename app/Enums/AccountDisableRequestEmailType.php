<?php

namespace App\Enums;

enum AccountDisableRequestEmailType: string
{
    case Submitted = 'submitted';
    case Disabled = 'disabled';
    case Banned = 'banned';
    case Dismissed = 'dismissed';
    case Reactivated = 'reactivated';

    public function subjectSuffix(): string
    {
        return match ($this) {
            self::Submitted   => 'Account Disable Request Received',
            self::Disabled    => 'Your Account Has Been Disabled',
            self::Banned      => 'Your Account Has Been Suspended',
            self::Dismissed   => 'Account Disable Request Update',
            self::Reactivated => 'Your Account Has Been Reactivated',
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::Submitted   => 'emails.account-disable-request-submitted',
            self::Disabled    => 'emails.account-disable-request-disabled',
            self::Banned      => 'emails.account-disable-request-banned',
            self::Dismissed   => 'emails.account-disable-request-dismissed',
            self::Reactivated => 'emails.admin-account-reactivated',
        };
    }
}
