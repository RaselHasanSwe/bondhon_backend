<?php

namespace App\Mail;

use App\Enums\SubscriptionEmailType;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SiteSettingService;
use App\Services\SubscriptionPaymentMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentMailable extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array<string, mixed> */
    private array $invoiceData;

    public function __construct(
        public readonly User $user,
        public readonly Subscription $subscription,
        public readonly SubscriptionEmailType $emailType,
        public readonly string $invoicePdf,
    ) {
        $this->invoiceData = app(SubscriptionPaymentMailService::class)->invoiceData($subscription);
    }

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
            with: array_merge($this->invoiceData, [
                'user' => $this->user,
                'subscriptionUrl' => rtrim(config('app.frontend_url', config('app.url')), '/')
                    . '/subscription',
            ]),
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $filename = 'invoice-' . $this->subscription->transaction_id . '.pdf';

        return [
            Attachment::fromData(fn () => $this->invoicePdf, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
