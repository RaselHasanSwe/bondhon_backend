<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('frontend.base_url', 'http://localhost:3000');

        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Your Password – MyBouma')
            ->view('emails.reset-password', [
                'url'  => $resetUrl,
                'user' => $notifiable,
            ]);
    }
}

