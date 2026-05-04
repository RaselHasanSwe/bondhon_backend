<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Subscription\InitiateSubscriptionRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionFeatureService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends ApiController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionFeatureService $featureService,
    ) {}

    /**
     * GET /api/v1/subscription/plans
     */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->orderBy('price_bdt')
            ->get();

        return $this->successResponse($plans, 'Subscription plans retrieved.');
    }

    /**
     * POST /api/v1/subscription/initiate
     * Allow buying any plan. Multiple active subscriptions are OK — user can switch between them.
     */
    public function initiate(InitiateSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($request->validated()['plan_id']);

        Log::info('[SUBSCRIPTION - API Initiate] User ID: ' . $user->id . ' | Plan: ' . $plan->slug);

        try {
            $result = $this->subscriptionService->initiate($user, $plan);
            return $this->successResponse($result, 'Payment initiated. Redirect to payment_url to complete.');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), null, 400);
        }
    }

    /**
     * GET /api/v1/subscription/status
     * Returns current plan, active subscription, and all switchable (paid & not-expired) subscriptions.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('activeSubscription.subscriptionPlan');

        // Resolve which subscription is currently active
        $activeSub = null;
        if ($user->active_subscription_id) {
            $activeSub = Subscription::with('subscriptionPlan')
                ->where('id', $user->active_subscription_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();
        }
        // Fallback if active_subscription_id is stale / not set
        if (! $activeSub) {
            $activeSub = Subscription::with('subscriptionPlan')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->latest('expires_at')
                ->first();
        }

        // All non-expired paid subscriptions available for switching
        $switchable = Subscription::with('subscriptionPlan:id,name,plan_type,price_bdt,duration_qty,duration_unit,features')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->get()
            ->map(fn ($s) => [
                'id'                  => $s->id,
                'subscription_plan_id'=> $s->subscription_plan_id,
                'plan'                => $s->plan,
                'amount_bdt'          => (float) $s->amount_bdt,
                'expires_at'          => $s->expires_at,
                'is_current'          => $s->id === ($activeSub?->id),
                'subscription_plan'   => $s->subscriptionPlan,
            ]);

        $isActive = $activeSub && $activeSub->expires_at->isFuture();

        return $this->successResponse([
            'plan'                   => $isActive ? ($activeSub->plan ?? $user->subscription_plan) : 'free',
            'expires_at'             => $activeSub?->expires_at ?? $user->subscription_expires_at,
            'is_active'              => $isActive,
            'active_subscription_id' => $activeSub?->id,
            'subscription'           => $activeSub,
            'switchable'             => $switchable,
            'features'               => $isActive ? $this->featureService->getPlanFeatures($user) : [],
        ], 'Subscription status retrieved.');
    }

    /**
     * POST /api/v1/subscription/{id}/switch
     * Switch the active plan to another paid & non-expired subscription.
     */
    public function switchPlan(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $subscription = Subscription::with('subscriptionPlan')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if (! $subscription) {
            return $this->errorResponse('Subscription not found or no longer valid.', null, 404);
        }

        if ($user->active_subscription_id === $subscription->id) {
            return $this->errorResponse('This plan is already active.', null, 422);
        }

        DB::transaction(function () use ($user, $subscription): void {
            $user->update([
                'subscription_plan'       => $subscription->plan,
                'subscription_expires_at' => $subscription->expires_at,
                'active_subscription_id'  => $subscription->id,
            ]);
        });

        // Bust the feature cache for this user
        Cache::forget("user_plan:{$user->id}");

        Log::info('[SUBSCRIPTION - Switch] User ID: ' . $user->id . ' → Subscription ID: ' . $subscription->id . ' (' . $subscription->plan . ')');

        return $this->successResponse([
            'active_subscription_id' => $subscription->id,
            'plan'                   => $subscription->plan,
            'expires_at'             => $subscription->expires_at,
            'subscription_plan'      => $subscription->subscriptionPlan,
        ], 'Plan switched successfully.');
    }

    /**
     * GET /api/v1/subscription/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = Subscription::with('subscriptionPlan:id,name,plan_type,price_bdt,duration_qty,duration_unit')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($sub) => [
                'id'                => $sub->id,
                'plan_name'         => $sub->subscriptionPlan?->name ?? ucfirst($sub->plan),
                'plan_type'         => $sub->plan,
                'amount_bdt'        => (float) $sub->amount_bdt,
                'payment_method'    => $sub->payment_method,
                'transaction_id'    => $sub->transaction_id,
                'status'            => $sub->status,
                'starts_at'         => $sub->starts_at,
                'expires_at'        => $sub->expires_at,
                'created_at'        => $sub->created_at,
                'is_current'        => $sub->id === $user->active_subscription_id,
                'is_switchable'     => $sub->status === 'active'
                                    && $sub->expires_at !== null
                                    && $sub->expires_at > now()
                                    && $sub->id !== $user->active_subscription_id,
                'subscription_plan' => $sub->subscriptionPlan,
            ]);

        return $this->successResponse($subscriptions, 'Payment history retrieved.');
    }
}

