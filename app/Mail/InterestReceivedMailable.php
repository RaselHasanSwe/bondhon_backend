<?php

namespace App\Mail;

use App\Models\User;
use App\Services\SiteSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterestReceivedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $receiver,
        public readonly User $sender,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = app(SiteSettingService::class)->get('site_name', config('app.name', 'Bondhon'));

        return new Envelope(
            subject: $siteName . ' - You have a new interest!',
        );
    }

    public function content(): Content
    {
        $profile   = $this->sender->profile;
        $primaryPhoto = $this->sender->photos
            ?->firstWhere('is_primary', true)
            ?? $this->sender->photos?->first();

        $profileId = $profile?->profile_id;

        return new Content(
            view: 'emails.interest-received',
            with: [
                'receiver' => $this->receiver,
                'sender'   => $this->sender,
                'senderSummary' => [
                    'name'      => $this->sender->name,
                    'age'       => $profile?->dob?->age,
                    'city'      => $profile?->city,
                    'state'     => $profile?->state,
                    'country'   => $profile?->country,
                    'religion'  => $this->sender->religiousDetail?->religion,
                    'education' => $this->sender->educationCareer?->highest_education,
                    'profession'=> $this->sender->educationCareer?->profession,
                    'photo_url' => profilePhotoUrl($primaryPhoto?->file_path),
                    'profile_url' => $profileId
                        ? rtrim(config('app.frontend_url', config('app.url')), '/')
                          . '/profile/' . $profileId
                        : rtrim(config('app.frontend_url', config('app.url')), '/')
                          . '/interests/received',
                ],
                'interestsUrl' => rtrim(config('app.frontend_url', config('app.url')), '/')
                    . '/interests',
            ],
        );
    }
}
