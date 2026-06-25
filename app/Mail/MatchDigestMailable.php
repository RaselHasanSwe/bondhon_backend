<?php

namespace App\Mail;

use App\Models\MatchScore;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MatchDigestMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  User         $user       The recipient
     * @param  MatchScore[] $topMatches Eager-loaded MatchScore records
     */
    public function __construct(
        public readonly User  $user,
        public readonly array $topMatches
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💍 Your Daily Match Digest — Enorsia',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.match-digest',
            with: [
                'user'       => $this->user,
                'topMatches' => collect($this->topMatches)->map(fn (MatchScore $ms) => [
                    'name'      => $ms->candidate?->name ?? 'A Member',
                    'score'     => round($ms->score),
                    'profile'   => $ms->candidate?->profile,
                    'education' => $ms->candidate?->educationCareer?->highest_education,
                    'religion'  => $ms->candidate?->religiousDetail?->religion,
                    'url'       => config('app.frontend_url', config('app.url'))
                                   . '/profile/' . ($ms->candidate?->profile?->profile_id ?? $ms->candidate_id),
                ])->values()->all(),
            ],
        );
    }
}

