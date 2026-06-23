<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminSubscriptionController extends ApiController
{
    // -----------------------------------------------------------------------
    // Subscription Plans (CRUD)
    // -----------------------------------------------------------------------

    /**
     * GET /api/v1/admin/subscription-plans
     */
    public function plans(Request $request): JsonResponse
    {
        $plans = SubscriptionPlan::withCount('subscriptions')
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse($plans, 'Subscription plans retrieved.');
    }

    /**
     * POST /api/v1/admin/subscription-plans
     */
    public function createPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'plan_type'     => ['nullable', 'string', 'max:50'],
            'price_bdt'     => ['required', 'integer', 'min:0'],
            'duration_qty'  => ['required', 'integer', 'min:1'],
            'duration_unit' => ['required', 'in:hour,day,month,year'],
            'features'      => ['nullable', 'array'],
            'is_active'     => ['boolean'],
            'sort_order'    => ['integer', 'min:0'],
        ]);

        Log::info('[ADMIN SUBSCRIPTION - CreatePlan] Admin ID: ' . $request->user()->id . ' | Plan: ' . $validated['name']);

        $featuresObj = $this->buildFeaturesObject($request->input('features', []));

        $slug = Str::slug($validated['name']);
        $baseSlug = $slug;
        $i = 1;
        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        $plan = DB::transaction(fn () => SubscriptionPlan::create([
            'name'          => $validated['name'],
            'slug'          => $slug,
            'description'   => $validated['description'] ?? null,
            'plan_type'     => $validated['plan_type'] ?? '',
            'price_bdt'     => $validated['price_bdt'],
            'duration_qty'  => $validated['duration_qty'],
            'duration_unit' => $validated['duration_unit'],
            'features'      => $featuresObj,
            'is_active'     => $validated['is_active'] ?? true,
            'sort_order'    => $validated['sort_order'] ?? 0,
        ]));

        Log::info('[ADMIN SUBSCRIPTION - CreatePlan] Created plan ID: ' . $plan->id);

        return $this->successResponse($plan, 'Subscription plan created.', 201);
    }

    /**
     * PUT /api/v1/admin/subscription-plans/{id}
     */
    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name'          => ['sometimes', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'plan_type'     => ['nullable', 'string', 'max:50'],
            'price_bdt'     => ['sometimes', 'integer', 'min:0'],
            'duration_qty'  => ['sometimes', 'integer', 'min:1'],
            'duration_unit' => ['sometimes', 'in:hour,day,month,year'],
            'features'      => ['sometimes', 'array'],
            'is_active'     => ['boolean'],
            'sort_order'    => ['integer', 'min:0'],
        ]);

        Log::info('[ADMIN SUBSCRIPTION - UpdatePlan] Admin ID: ' . $request->user()->id . ' | Plan ID: ' . $id);

        if ($request->has('features')) {
            $validated['features'] = $this->buildFeaturesObject($request->input('features', []));
        }

        DB::transaction(fn () => $plan->update($validated));

        return $this->successResponse($plan->fresh(), 'Subscription plan updated.');
    }

    /**
     * DELETE /api/v1/admin/subscription-plans/{id}
     */
    public function deletePlan(Request $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        if ($plan->price_bdt === 0) {
            return $this->errorResponse('Free plans (price ৳0) cannot be deleted. They are required for new user registration.', null, 422);
        }

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return $this->errorResponse('Cannot delete plan with active subscriptions.', null, 422);
        }

        Log::info('[ADMIN SUBSCRIPTION - DeletePlan] Admin ID: ' . $request->user()->id . ' | Plan ID: ' . $id);

        $plan->delete();

        return $this->successResponse(null, 'Subscription plan deleted.');
    }

    // -----------------------------------------------------------------------
    // Subscription Sales & Revenue
    // -----------------------------------------------------------------------

    /**
     * GET /api/v1/admin/subscriptions
     * List all user subscriptions with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        Log::info('[ADMIN SUBSCRIPTION - Index] Admin ID: ' . $request->user()->id);

        $query = Subscription::with(['user:id,name,email', 'subscriptionPlan:id,name,plan_type'])
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
            );
        }

        $subscriptions = $query->paginate(20);

        return $this->successResponse($subscriptions, 'Subscriptions retrieved.');
    }

    /**
     * GET /api/v1/admin/subscriptions/stats
     * Revenue & subscription statistics for admin dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        Log::info('[ADMIN SUBSCRIPTION - Stats] Admin ID: ' . $request->user()->id);

        $stats = [
            'total_revenue_bdt'       => (int) Subscription::where('status', 'active')->sum('amount_bdt'),
            'this_month_revenue_bdt'  => (int) Subscription::where('status', 'active')
                                                ->whereMonth('created_at', now()->month)
                                                ->whereYear('created_at', now()->year)
                                                ->sum('amount_bdt'),
            'active_subscriptions'    => Subscription::where('status', 'active')->where('expires_at', '>', now())->count(),
            'total_paid'              => Subscription::where('status', 'active')->count(),
            'pending_payments'        => Subscription::where('status', 'pending')->count(),
            'by_plan'                 => Subscription::where('status', 'active')
                                            ->selectRaw('plan, COUNT(*) as count, SUM(amount_bdt) as revenue')
                                            ->groupBy('plan')
                                            ->get(),
            'monthly_revenue'         => Subscription::where('status', 'active')
                                            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(amount_bdt) as revenue, COUNT(*) as count')
                                            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                                            ->orderByRaw('YEAR(created_at) DESC, MONTH(created_at) DESC')
                                            ->limit(12)
                                            ->get(),
        ];

        return $this->successResponse($stats, 'Subscription statistics retrieved.');
    }

    // -----------------------------------------------------------------------
    // Private Helpers
    // -----------------------------------------------------------------------

    /**
     * Build structured features object from an array input.
     * Accepts both API payloads ({key: value}) and form-style inputs.
     */
    private function buildFeaturesObject(array $raw): array
    {
        $defs   = SubscriptionFeatureService::definitions();
        $result = [];

        foreach ($defs as $key => $def) {
            if (! array_key_exists($key, $raw)) {
                // Keep feature absent (will fall back to default at runtime)
                continue;
            }
            if ($def['type'] === 'bool') {
                $result[$key] = filter_var($raw[$key], FILTER_VALIDATE_BOOLEAN);
            } elseif ($def['type'] === 'qty') {
                $result[$key] = (int) $raw[$key];
            } elseif ($def['type'] === 'enum') {
                $result[$key] = in_array($raw[$key], $def['options']) ? $raw[$key] : $def['default'];
            }
        }

        return $result;
    }
}

