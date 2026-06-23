<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckFeature middleware
 *
 * Gates a route behind a boolean subscription feature flag.
 *
 * Usage in routes:
 *   ->middleware('feature:chat_access')
 *   ->middleware('feature:audio_call_access')
 *   ->middleware('feature:video_call_access')
 */
class CheckFeature
{
    public function __construct(private readonly SubscriptionFeatureService $featureService) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Unauthenticated.',
                'errors'  => null,
            ], 401);
        }

        if (! $this->featureService->can($user, $feature)) {
            $defs  = SubscriptionFeatureService::definitions();
            $label = $defs[$feature]['label'] ?? $feature;

            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Your current subscription plan does not include: ' . $label . '.',
                'errors'  => ['feature' => $feature],
            ], 403);
        }

        return $next($request);
    }
}

