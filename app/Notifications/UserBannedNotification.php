<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('Your Enorsia Account Has Been Suspended')
            ->view('emails.account-banned', [
                'user'   => $notifiable,
                'reason' => $this->reason,
            ]);
    }
}
