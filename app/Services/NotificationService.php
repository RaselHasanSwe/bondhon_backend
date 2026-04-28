<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService — Phase 3
 *
 * Handles in-app notification creation and dispatching.
 */
class NotificationService
{
    /**
     * TODO: Phase 3 — Send a notification to a user.
     */
    public function send(User $user, string $type, array $data): void
    {
        Log::info('[NOTIFICATION - Send] Phase 3 not yet implemented. Type: ' . $type);
    }
}

