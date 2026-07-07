<?php

namespace App\Jobs;

use App\Enums\SubscriptionEmailType;
use App\Mail\SubscriptionPaymentMailable;
use App\Models\Subscription;
use App\Services\SubscriptionPaymentMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionPaymentEmail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $subscriptionId,
    ) {
        $this->delay(
            now()->addSeconds(config('notifications.subscription_payment_email_delay_seconds', 60))
        );
    }

    public function uniqueId(): string
    {
        return 'subscription-payment-email-' . $this->subscriptionId;
    }

    public function handle(SubscriptionPaymentMailService $mailService): void
    {
        $subscription = Subscription::query()
            ->with(['user', 'subscriptionPlan'])
            ->find($this->subscriptionId);

        if (! $subscription) {
            Log::warning('[SUBSCRIPTION PAYMENT EMAIL] Subscription not found. ID: ' . $this->subscriptionId);

            return;
        }

        if ($subscription->status !== 'active' || (float) $subscription->amount_bdt <= 0) {
            Log::info('[SUBSCRIPTION PAYMENT EMAIL] Skipped — not an active paid subscription. ID: ' . $this->subscriptionId);

            return;
        }

        $user = $subscription->user;

        if (! $user || ! $user->email || $user->is_banned || ! $user->is_active) {
            Log::info('[SUBSCRIPTION PAYMENT EMAIL] Skipped — user cannot receive email. User ID: ' . ($user?->id ?? 'n/a'));

            return;
        }

        Log::info('[SUBSCRIPTION PAYMENT EMAIL] Sending. Subscription ID: ' . $subscription->id
            . ' | User ID: ' . $user->id
            . ' | TxID: ' . $subscription->transaction_id);

        $invoicePdf = $mailService->generateInvoicePdf($subscription);

        Mail::to($user->email)->send(new SubscriptionPaymentMailable(
            $user,
            $subscription,
            SubscriptionEmailType::PaymentSuccess,
            $invoicePdf,
        ));
    }
}
