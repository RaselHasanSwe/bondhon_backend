<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserBanService
{
    public function disable(User $user, string $reason): void
    {
        $user->update([
            'is_active'      => false,
            'disable_reason' => $reason,
            'disabled_at'    => now(),
        ]);

        $user->tokens()->delete();

        Log::info('[USER DISABLE] User ID: ' . $user->id . ' disabled.', [
            'reason' => $reason,
        ]);
    }

    public function ban(User $user, string $reason, bool $sendEmail = false): void
    {
        $user->update([
            'is_banned'      => true,
            'is_active'      => false,
            'ban_reason'     => $reason,
            'banned_at'      => now(),
            'disable_reason' => null,
            'disabled_at'    => null,
        ]);

        $user->tokens()->delete();

        if ($sendEmail) {
            $user->notify(new \App\Notifications\UserBannedNotification($reason));
        }

        Log::info('[USER BAN] User ID: ' . $user->id . ' banned.', [
            'reason'     => $reason,
            'send_email' => $sendEmail,
        ]);
    }

    public function reactivate(User $user): void
    {
        $user->update([
            'is_banned'      => false,
            'is_active'      => true,
            'ban_reason'     => null,
            'banned_at'      => null,
            'disable_reason' => null,
            'disabled_at'    => null,
        ]);

        Log::info('[USER BAN] User ID: ' . $user->id . ' reactivated.');
    }
}
