<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;

class PublicSubscriptionPlanController extends Controller
{
    /**
     * GET /api/v1/subscription-plans
     * Active subscription plans + feature labels for the public pricing page.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->with('subscriptionType:id,name')
            ->orderBy('sort_order')
            ->orderBy('price_bdt')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'plans'               => $plans,
                'feature_definitions' => SubscriptionFeatureService::publicDefinitions(),
            ],
            'message' => 'Subscription plans retrieved successfully.',
            'errors'  => [],
        ]);
    }
}
