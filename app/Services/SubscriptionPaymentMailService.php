<?php

namespace App\Services;

use App\Jobs\SendSubscriptionPaymentEmail;
use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentMailService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SiteSettingService $siteSettings,
    ) {}

    /**
     * Send in-app notification and queue the payment confirmation email.
     */
    public function sendPaymentConfirmation(Subscription $subscription): void
    {
        $subscription->loadMissing(['user', 'subscriptionPlan']);

        $user = $subscription->user;

        if (! $user) {
            Log::warning('[SUBSCRIPTION PAYMENT MAIL] Missing user. Subscription ID: ' . $subscription->id);

            return;
        }

        $this->notificationService->notifySubscriptionActivated($user, $subscription);

        SendSubscriptionPaymentEmail::dispatch($subscription->id);

        Log::info('[SUBSCRIPTION PAYMENT MAIL] Queued confirmation. Subscription ID: ' . $subscription->id
            . ' | User ID: ' . $user->id);
    }

    /**
     * Build invoice data for email and PDF views.
     *
     * @return array<string, mixed>
     */
    public function invoiceData(Subscription $subscription): array
    {
        $subscription->loadMissing(['user', 'subscriptionPlan']);

        $plan = $subscription->subscriptionPlan;
        $currencySymbol = $this->siteSettings->get('currency_symbol', '৳');

        return [
            'subscription'     => $subscription,
            'user'             => $subscription->user,
            'planName'         => $plan?->name ?? ucfirst((string) $subscription->plan),
            'planType'         => $subscription->plan,
            'durationLabel'    => $this->formatPlanDuration($plan),
            'amountFormatted'  => number_format((float) $subscription->amount_bdt, 0),
            'currencySymbol'   => $currencySymbol,
            'siteName'         => $this->siteSettings->get('site_name', config('app.name', 'Bondhon')),
            'siteSlogan'       => $this->siteSettings->get('site_slogan', ''),
            'contactEmail'     => $this->siteSettings->get('contact_email', ''),
            'contactAddress'   => $this->siteSettings->get('contact_address', ''),
        ];
    }

    /**
     * Generate subscription invoice PDF bytes.
     */
    public function generateInvoicePdf(Subscription $subscription): string
    {
        return Pdf::loadView('pdf.subscription-invoice', $this->invoiceData($subscription))
            ->setPaper('a4')
            ->output();
    }

    private function formatPlanDuration(?\App\Models\SubscriptionPlan $plan): string
    {
        if (! $plan) {
            return '—';
        }

        $qty = $plan->duration_qty ?? 1;
        $unit = $plan->duration_unit ?? 'day';

        return $qty . ' ' . str($unit)->plural($qty);
    }
}
