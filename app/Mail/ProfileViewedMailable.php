<?php

namespace App\Mail;

use App\Models\User;
use App\Services\SiteSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileViewedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $viewed,
        public readonly User $viewer,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = app(SiteSettingService::class)->get('site_name', config('app.name', 'Bondhon'));

        return new Envelope(
            subject: $siteName . ' - Someone viewed your profile!',
        );
    }

    public function content(): Content
    {
        $profile = $this->viewer->profile;
        $primaryPhoto = $this->viewer->photos
            ?->firstWhere('is_primary', true)
            ?? $this->viewer->photos?->first();

        $profileId = $profile?->profile_id;

        return new Content(
            view: 'emails.profile-viewed',
            with: [
                'viewed' => $this->viewed,
                'viewer' => $this->viewer,
                'viewerSummary' => [
                    'name'        => $this->viewer->name,
                    'age'         => $profile?->dob?->age,
                    'city'        => $profile?->city,
                    'state'       => $profile?->state,
                    'country'     => $profile?->country,
                    'religion'    => $this->viewer->religiousDetail?->religion,
                    'education'   => $this->viewer->educationCareer?->highest_education,
                    'profession'  => $this->viewer->educationCareer?->profession,
                    'photo_url'   => profilePhotoUrl($primaryPhoto?->file_path),
                    'profile_url' => $profileId
                        ? rtrim(config('app.frontend_url', config('app.url')), '/')
                          . '/profile/' . $profileId
                        : rtrim(config('app.frontend_url', config('app.url')), '/')
                          . '/profile-views',
                ],
                'profileViewsUrl' => rtrim(config('app.frontend_url', config('app.url')), '/')
                    . '/profile-views',
            ],
        );
    }
}
