<?php

namespace App\Mail;

use App\Models\User;
use App\Services\SiteSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MatchDigestMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $matchSummaries
     */
    public function __construct(
        public readonly User $user,
        public readonly array $matchSummaries,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = app(SiteSettingService::class)->get('site_name', config('app.name', 'Bondhon'));
        $count    = count($this->matchSummaries);

        return new Envelope(
            to: [new Address($this->user->email, $this->user->name ?? 'Member')],
            subject: $siteName . ' - ' . $count . ' New Match' . ($count === 1 ? '' : 'es') . ' Today',
        );
    }

    public function content(): Content
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');

        return new Content(
            view: 'emails.match-digest',
            with: [
                'user'       => $this->user,
                'matchesUrl' => $frontend . '/matches',
                'topMatches' => $this->matchSummaries,
            ],
        );
    }
}
