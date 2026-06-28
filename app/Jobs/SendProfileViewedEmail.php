<?php

namespace App\Jobs;

use App\Mail\ProfileViewedMailable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendProfileViewedEmail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $viewerId,
        public readonly int $viewedId,
    ) {
        $this->delay(
            now()->addSeconds(config('notifications.profile_viewed_email_delay_seconds', 60))
        );
    }

    public function uniqueId(): string
    {
        return 'profile-viewed-email-' . $this->viewerId . '-' . $this->viewedId . '-' . today()->toDateString();
    }

    public function handle(): void
    {
        $viewer = User::query()
            ->with([
                'profile',
                'religiousDetail',
                'educationCareer',
                'photos' => fn ($q) => $q
                    ->where('is_approved', true)
                    ->orderByDesc('is_primary'),
            ])
            ->find($this->viewerId);

        $viewed = User::query()->find($this->viewedId);

        if (! $viewer || ! $viewed) {
            Log::warning('[PROFILE VIEW EMAIL] User not found. Viewer: ' . $this->viewerId . ' | Viewed: ' . $this->viewedId);

            return;
        }

        if (! $viewed->email || $viewed->is_banned || ! $viewed->is_active) {
            Log::info('[PROFILE VIEW EMAIL] Skipped — viewed user cannot receive email. User ID: ' . $viewed->id);

            return;
        }

        Log::info('[PROFILE VIEW EMAIL] Sending. Viewer: ' . $viewer->id . ' → Viewed: ' . $viewed->id);

        Mail::to($viewed->email)->send(new ProfileViewedMailable($viewed, $viewer));
    }
}
