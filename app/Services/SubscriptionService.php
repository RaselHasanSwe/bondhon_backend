<?php

namespace App\Services;

use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SubscriptionService — handles SSLCommerz payment initiation and activation.
 */
class SubscriptionService
{
    /**
     * Initiate a subscription payment via SSLCommerz.
     *
     * @return array{payment_url: string, transaction_id: string}
     * @throws \RuntimeException
     */
    public function initiate(User $user, SubscriptionPlan $plan): array
    {
        Log::info('[SUBSCRIPTION - Initiate] User ID: ' . $user->id . ' | Plan: ' . $plan->slug);

        $transactionId = 'BON-' . strtoupper(Str::random(12)) . '-' . time();

        $subscription = DB::transaction(function () use ($user, $plan, $transactionId): Subscription {
            return Subscription::create([
                'user_id'              => $user->id,
                'subscription_plan_id' => $plan->id,
                'plan'                 => $plan->plan_type,
                'amount_bdt'           => $plan->price_bdt,
                'payment_method'       => 'sslcommerz',
                'transaction_id'       => $transactionId,
                'status'               => 'pending',
                'starts_at'            => now(),
                'expires_at'           => now()->addDays($plan->getDurationInDays()),
            ]);
        });

        $postData = [
            'total_amount'     => $plan->price_bdt,
            'currency'         => 'BDT',
            'tran_id'          => $transactionId,
            'success_url'      => config('sslcommerz.success_url'),
            'fail_url'         => config('sslcommerz.fail_url'),
            'cancel_url'       => config('sslcommerz.cancel_url'),
            'ipn_url'          => config('sslcommerz.ipn_url'),
            'cus_name'         => $user->name,
            'cus_email'        => $user->email,
            'cus_add1'         => 'Bangladesh',
            'cus_city'         => 'Dhaka',
            'cus_country'      => 'Bangladesh',
            'cus_phone'        => '01700000000',
            'shipping_method'  => 'NO',
            'product_name'     => $plan->name,
            'product_category' => 'Subscription',
            'product_profile'  => 'non-physical-goods',
            'num_of_item'      => 1,
        ];

        $sslcz    = new SslCommerzNotification();
        $response = $sslcz->makePayment($postData, 'hosted', 'json');

        if (empty($response) || ($response['status'] ?? '') !== 'SUCCESS' || empty($response['GatewayPageURL'])) {
            $subscription->delete();
            $errorMsg = $response['failedreason'] ?? 'SSLCommerz initiation failed.';
            Log::error('[SUBSCRIPTION - Initiate] Failed for User ID: ' . $user->id . ' | Reason: ' . $errorMsg);
            throw new \RuntimeException($errorMsg);
        }

        Log::info('[SUBSCRIPTION - Initiate] URL generated for User ID: ' . $user->id . ' | TxID: ' . $transactionId);

        return [
            'payment_url'    => $response['GatewayPageURL'],
            'transaction_id' => $transactionId,
        ];
    }

    /**
     * Activate subscription after SSLCommerz payment success callback.
     *
     * @param  array<string, mixed> $callbackData
     * @throws \RuntimeException
     */
    public function activate(array $callbackData): Subscription
    {
        $transactionId = $callbackData['tran_id'] ?? '';

        Log::info('[SUBSCRIPTION - Activate] Callback for TxID: ' . $transactionId);

        $subscription = Subscription::with(['user', 'subscriptionPlan'])
            ->where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->first();

        if (! $subscription) {
            Log::warning('[SUBSCRIPTION - Activate] Not found or already processed. TxID: ' . $transactionId);
            throw new \RuntimeException('Subscription record not found.');
        }

        $sslcz   = new SslCommerzNotification();
        $isValid = $sslcz->orderValidate(
            $callbackData,
            $transactionId,
            (float) $subscription->amount_bdt,
            'BDT'
        );

        if (! $isValid) {
            Log::warning('[SUBSCRIPTION - Activate] Validation failed. TxID: ' . $transactionId);
            throw new \RuntimeException('Payment validation failed.');
        }

        $plan       = $subscription->subscriptionPlan;
        $startDate  = now();
        $expireDate = $startDate->copy()->addDays($plan->getDurationInDays());

        DB::transaction(function () use ($subscription, $callbackData, $startDate, $expireDate): void {
            $subscription->update([
                'status'         => 'active',
                'payment_method' => $callbackData['card_type'] ?? 'SSLCommerz',
                'starts_at'      => $startDate,
                'expires_at'     => $expireDate,
            ]);

            // Update user's active plan — new plan becomes the active one immediately
            $subscription->user->update([
                'subscription_plan'       => $subscription->plan,
                'subscription_expires_at' => $expireDate,
                'active_subscription_id'  => $subscription->id,
            ]);
        });

        Log::info('[SUBSCRIPTION - Activate] Activated. User ID: ' . $subscription->user_id . ' | Plan: ' . $subscription->plan . ' | Expires: ' . $expireDate);

        return $subscription->fresh(['user', 'subscriptionPlan']);
    }

    /**
     * Mark a pending subscription as failed / cancelled.
     */
    public function markFailed(string $transactionId, string $reason = 'failed'): void
    {
        Log::info('[SUBSCRIPTION - MarkFailed] TxID: ' . $transactionId . ' | Reason: ' . $reason);

        Subscription::where('transaction_id', $transactionId)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);
    }
}
