<?php

namespace App\Notifications;

use App\Services\SiteSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FaceScanApprovedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(SiteSettingService::class);
        $siteName = $settings->get('site_name', config('app.name', 'Bondhon'));

        return (new MailMessage)
            ->subject($siteName . ' - Face Verification Approved')
            ->view('emails.face-scan-approved', [
                'user' => $notifiable,
            ]);
    }
}
