<?php

namespace App\Notifications;

use App\Mail\MatchDigestMailable;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * One digest per user: all matches in a single in-app notification and one email.
 *
 * @param  array<int, array<string, mixed>>  $matchSummaries  Plain match rows from MatchingService::buildDigestMatchSummaries()
 */
class MatchDigestNotification extends Notification
{
    public function __construct(
        public readonly array $matchSummaries,
        public readonly bool $sendEmail = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendEmail && filled($notifiable->email ?? null) && $this->matchSummaries !== []) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MatchDigestMailable
    {
        /** @var User $notifiable */
        return (new MatchDigestMailable($notifiable, $this->matchSummaries))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->matchSummaries);

        return [
            'type'    => 'match_digest',
            'title'   => 'Your Daily Match Digest',
            'message' => $count === 1
                ? 'You have 1 new top match today!'
                : 'You have ' . $count . ' new top matches today!',
            'matches' => collect($this->matchSummaries)->map(fn (array $m) => [
                'candidate_id'   => $m['candidate_id'] ?? null,
                'candidate_name' => $m['name'] ?? 'Someone',
                'score'          => $m['score'] ?? 0,
                'photo_url'      => $m['photo_url'] ?? null,
                'profile_url'    => $m['profile_url'] ?? null,
            ])->values()->all(),
        ];
    }
}
