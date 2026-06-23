<?php

namespace App\Notifications;

use App\Mail\MatchDigestMailable;
use App\Models\MatchScore;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  MatchScore[] $topMatches  Top match score records (eager-loaded candidate)
     * @param  bool         $sendEmail   Whether to also send the email digest.
     *                                   Controlled by the user's `email_digest_frequency`
     *                                   subscription feature ('daily' | 'weekly' | 'none').
     */
    public function __construct(
        public readonly array $topMatches,
        public readonly bool  $sendEmail = false,
    ) {}

    /**
     * Channels: always store in-app (database); only email when $sendEmail is true.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MatchDigestMailable
    {
        /** @var User $notifiable */
        return new MatchDigestMailable($notifiable, $this->topMatches);
    }

    public function toArray(object $notifiable): array
    {
        $matches = collect($this->topMatches)->map(fn ($ms) => [
            'candidate_id'   => $ms->candidate_id,
            'candidate_name' => $ms->candidate?->name ?? 'Someone',
            'score'          => round($ms->score),
        ])->values()->all();

        return [
            'type'    => 'match_digest',
            'title'   => 'Your Daily Match Digest',
            'message' => 'You have ' . count($matches) . ' new top matches today!',
            'matches' => $matches,
        ];
    }
}
