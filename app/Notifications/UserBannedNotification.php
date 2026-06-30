<?php

namespace App\Notifications;

use App\Services\SiteSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserBannedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = app(SiteSettingService::class)->get('site_name', config('app.name', 'Bondhon'));

        return (new MailMessage)
            ->subject($siteName . ' - Your account has been suspended')
            ->view('emails.account-banned', [
                'user'   => $notifiable,
                'reason' => $this->reason,
            ]);
    }
}
