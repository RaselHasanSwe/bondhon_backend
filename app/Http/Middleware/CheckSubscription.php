<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('subscription:gold,platinum')
     */
    public function handle(Request $request, Closure $next, string ...$plans): Response
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

        // If no specific plans are required, just allow through
        if (empty($plans)) {
            return $next($request);
        }

        $isExpired = $user->subscription_expires_at && $user->subscription_expires_at->isPast();
        $plan      = ($isExpired) ? 'free' : $user->subscription_plan;

        if (! in_array($plan, $plans)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'This feature requires an upgraded subscription plan.',
                'errors'  => ['subscription' => 'Required plan: ' . implode(' or ', $plans)],
            ], 403);
        }

        return $next($request);
    }
}

