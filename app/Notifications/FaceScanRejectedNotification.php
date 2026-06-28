<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FaceScanRejectedNotification extends Notification
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
        $settings = app(SiteSettingService::class);
        $siteName = $settings->get('site_name', config('app.name', 'Bondhon'));

        return (new MailMessage)
            ->subject($siteName. ' - Face Verification Requires Resubmission')
            ->view('emails.face-scan-rejected', [
                'user'   => $notifiable,
                'reason' => $this->reason,
            ]);
    }
}
