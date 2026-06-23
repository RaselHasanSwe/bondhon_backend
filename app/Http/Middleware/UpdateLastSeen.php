<?php

namespace App\Http\Middleware;

use App\Events\UserOnlineStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * UpdateLastSeen — updates user's last_seen_at on every API request (throttled).
 * Broadcasts online status change via Reverb.
 */
class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $cacheKey = 'user_seen_' . $user->id;

            // Only update every 60 seconds to avoid hammering DB
            if (!Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, now()->addSeconds(60));

                $profile = $user->profile;
                if ($profile) {
                    $wasOffline = $profile->last_seen_at === null
                        || $profile->last_seen_at->diffInMinutes(now()) > 5;

                    $profile->updateQuietly(['last_seen_at' => now()]);

                    // Broadcast online status change
                    if ($wasOffline) {
                        broadcast(new UserOnlineStatus($user, true));
                    }
                }
            }
        }

        return $next($request);
    }
}

