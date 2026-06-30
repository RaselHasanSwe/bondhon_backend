<?php

namespace App\Notifications;

use App\Services\EmailVerificationOtpService;
use App\Services\SiteSettingService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmail
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $settings = app(SiteSettingService::class);
        $siteName = $settings->get('site_name', config('app.name', 'Bondhon'));

        $otp = null;
        $expiresIn = null;

        if ($settings->boolean('email_verification_enabled', true)) {
            $otpService = app(EmailVerificationOtpService::class);
            $otp = $otpService->issue($notifiable);
            $expiresIn = $otpService->expiryMinutes() . ' minutes';
        }

        return (new MailMessage)
            ->subject($siteName . ' - Verify your email')
            ->view('emails.verify-email', [
                'user'      => $notifiable,
                'otp'       => $otp,
                'expiresIn' => $expiresIn,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
