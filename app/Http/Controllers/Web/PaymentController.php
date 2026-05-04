<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaymentController — handles SSLCommerz payment callbacks.
 * These routes are excluded from CSRF because SSLCommerz POSTs to them.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    /**
     * POST /payment/success
     * SSLCommerz redirects user here after successful payment.
     */
    public function success(Request $request): RedirectResponse
    {
        $transactionId = $request->input('tran_id', '');
        Log::info('[PAYMENT - Success] Callback received. TxID: ' . $transactionId);

        try {
            $subscription = $this->subscriptionService->activate($request->all());

            return redirect(
                config('frontend.base_url') . '/subscription/success?plan=' . urlencode($subscription->plan)
            );

        } catch (\RuntimeException $e) {
            Log::error('[PAYMENT - Success] Activation failed. TxID: ' . $transactionId . ' | Error: ' . $e->getMessage());

            return redirect(
                config('frontend.base_url') . '/subscription/cancelled?reason=' . urlencode($e->getMessage())
            );
        }
    }

    /**
     * POST /payment/fail
     * SSLCommerz redirects user here after payment failure.
     */
    public function fail(Request $request): RedirectResponse
    {
        $transactionId = $request->input('tran_id', '');
        Log::warning('[PAYMENT - Fail] Payment failed. TxID: ' . $transactionId);

        $this->subscriptionService->markFailed($transactionId, 'payment_failed');

        return redirect(config('frontend.base_url') . '/subscription/cancelled?reason=payment_failed');
    }

    /**
     * POST /payment/cancel
     * SSLCommerz redirects user here when payment is cancelled.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $transactionId = $request->input('tran_id', '');
        Log::info('[PAYMENT - Cancel] Payment cancelled. TxID: ' . $transactionId);

        $this->subscriptionService->markFailed($transactionId, 'cancelled');

        return redirect(config('frontend.base_url') . '/subscription/cancelled?reason=cancelled');
    }

    /**
     * POST /payment/ipn
     * SSLCommerz Instant Payment Notification (background server-to-server callback).
     * Used as a fallback to activate subscriptions if the success redirect fails.
     */
    public function ipn(Request $request): \Illuminate\Http\Response
    {
        $transactionId = $request->input('tran_id', '');
        Log::info('[PAYMENT - IPN] Received. TxID: ' . $transactionId);

        try {
            // Only activate if still pending (success callback may have already handled it)
            $this->subscriptionService->activate($request->all());
        } catch (\RuntimeException $e) {
            Log::info('[PAYMENT - IPN] Skipped: ' . $e->getMessage() . ' TxID: ' . $transactionId);
        }

        return response('IPN received.', 200);
    }
}

