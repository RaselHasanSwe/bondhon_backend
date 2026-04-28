<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionService — Phase 4
 *
 * Handles SSLCommerz subscription payment initiation and activation.
 */
class SubscriptionService
{
    /**
     * TODO: Phase 4 — Initiate a subscription payment via SSLCommerz.
     */
    public function initiate(User $user, string $plan): array
    {
        Log::info('[SUBSCRIPTION - Initiate] Phase 4 not yet implemented. Plan: ' . $plan);
        return [];
    }

    /**
     * TODO: Phase 4 — Activate subscription after payment callback.
     */
    public function activate(string $transactionId): ?Subscription
    {
        Log::info('[SUBSCRIPTION - Activate] Phase 4 not yet implemented. TxID: ' . $transactionId);
        return null;
    }
}

