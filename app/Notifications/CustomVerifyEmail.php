<?php

namespace App\Notifications;

use App\Models\SiteSetting;
use App\Services\EmailVerificationOtpService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmail
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        // ✅ this keeps Laravel signed URL logic — points to the PUBLIC frontend verify page
        $backendUrl = $this->verificationUrl($notifiable);
        $frontendBase = rtrim(config('frontend.base_url', 'http://localhost:3000'), '/');
        $url = $frontendBase . '/verify-email?v_url=' . urlencode($backendUrl);

        $otp = null;
        $expiresIn = null;

        if (SiteSetting::booleanValue('email_verification_enabled', true)) {
            $otpService = app(EmailVerificationOtpService::class);
            $otp = $otpService->issue($notifiable);
            $expiresIn = $otpService->expiryMinutes() . ' minutes';
        }

        return (new MailMessage)
            ->subject('Verify Your Email')
            ->view('emails.verify-email', [
                'url'       => $url,
                'user'      => $notifiable,
                'otp'       => $otp,
                'expiresIn' => $expiresIn,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
