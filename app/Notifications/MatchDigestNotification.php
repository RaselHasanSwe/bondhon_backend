<?php

namespace App\Notifications;

use App\Mail\MatchDigestMailable;
use App\Models\MatchScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  MatchScore[]  $topMatches  Top match score records (eager-loaded candidate)
     */
    public function __construct(
        public readonly array $topMatches
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MatchDigestMailable
    {
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

