<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user    = $request->user();
        $profile = $user?->profile;

        if (! $profile || $profile->profile_completion_percentage < 50) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Your profile is incomplete. Please complete at least 50% of your profile.',
                'errors'  => ['profile' => 'Minimum 50% profile completion required.'],
            ], 403);
        }

        return $next($request);
    }
}

