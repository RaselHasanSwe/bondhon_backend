<?php

namespace App\Notifications;

use App\Services\SiteSettingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $settings = app(SiteSettingService::class);
        $siteName = $settings->get('site_name', config('app.name', 'Bondhon'));

        $frontendUrl = config('frontend.base_url', 'http://localhost:3000');

        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject($siteName . ' - Reset your password')
            ->view('emails.reset-password', [
                'url'  => $resetUrl,
                'user' => $notifiable,
            ]);
    }
}
