<?php

namespace App\Mail;

use App\Enums\AccountDisableRequestEmailType;
use App\Models\User;
use App\Services\SiteSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDisableRequestMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly AccountDisableRequestEmailType $emailType,
        public readonly ?string $adminMessage = null,
        public readonly ?string $requestTypeLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = app(SiteSettingService::class)->get('site_name', config('app.name', 'Bondhon'));

        return new Envelope(
            subject: $siteName . ' - ' . $this->emailType->subjectSuffix(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->emailType->view(),
            with: [
                'user'              => $this->user,
                'adminMessage'      => $this->adminMessage,
                'requestTypeLabel'  => $this->requestTypeLabel,
            ],
        );
    }
}
