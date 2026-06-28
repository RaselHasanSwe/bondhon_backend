<?php

namespace App\Services;

use App\Enums\AdminAccountActionEmailType;
use App\Jobs\SendAdminAccountActionEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UserAccountStatusService
{
    public function __construct(
        private readonly UserBanService $banService,
        private readonly NotificationService $notificationService,
    ) {}

    public function disableByAdmin(User $user, string $reason, User $admin): void
    {
        $this->assertNotSelf($user, $admin);
        $this->assertCanDisable($user);

        DB::transaction(function () use ($user, $reason) {
            $this->banService->disable($user, $reason);
        });

        $user->refresh();
        $timestamp = $user->disabled_at?->timestamp ?? now()->timestamp;

        $this->notificationService->notifyAdminAccountDisabled($user, $reason);
        SendAdminAccountActionEmail::dispatch(
            $user->id,
            AdminAccountActionEmailType::Disabled,
            $timestamp,
            $reason,
        );

        Log::info('[ADMIN ACCOUNT STATUS] Disabled', [
            'user_id'  => $user->id,
            'admin_id' => $admin->id,
        ]);
    }

    public function banByAdmin(User $user, string $reason, User $admin): void
    {
        $this->assertNotSelf($user, $admin);
        $this->assertCanBan($user);

        DB::transaction(function () use ($user, $reason) {
            $this->banService->ban($user, $reason, false);
        });

        $user->refresh();
        $timestamp = $user->banned_at?->timestamp ?? now()->timestamp;

        $this->notificationService->notifyAdminAccountBanned($user, $reason);
        SendAdminAccountActionEmail::dispatch(
            $user->id,
            AdminAccountActionEmailType::Banned,
            $timestamp,
            $reason,
        );

        Log::info('[ADMIN ACCOUNT STATUS] Banned', [
            'user_id'  => $user->id,
            'admin_id' => $admin->id,
        ]);
    }

    public function reactivateByAdmin(User $user, ?string $reason, User $admin): void
    {
        $this->assertNotSelf($user, $admin);
        $this->assertCanReactivate($user);

        DB::transaction(function () use ($user) {
            $this->banService->reactivate($user);
        });

        $user->refresh();
        $timestamp = now()->timestamp;

        $this->notificationService->notifyAdminAccountReactivated($user, $reason);
        SendAdminAccountActionEmail::dispatch(
            $user->id,
            AdminAccountActionEmailType::Reactivated,
            $timestamp,
            $reason,
        );

        Log::info('[ADMIN ACCOUNT STATUS] Reactivated', [
            'user_id'  => $user->id,
            'admin_id' => $admin->id,
        ]);
    }

    private function assertNotSelf(User $user, User $admin): void
    {
        if ($user->id === $admin->id) {
            throw new RuntimeException('You cannot change your own account status.');
        }
    }

    private function assertCanDisable(User $user): void
    {
        if ($user->is_banned) {
            throw new RuntimeException('Banned accounts cannot be disabled. Reactivate or keep the ban.');
        }

        if (! $user->is_active) {
            throw new RuntimeException('This account is already disabled.');
        }
    }

    private function assertCanBan(User $user): void
    {
        if ($user->is_banned) {
            throw new RuntimeException('This account is already banned.');
        }
    }

    private function assertCanReactivate(User $user): void
    {
        if ($user->is_active && ! $user->is_banned) {
            throw new RuntimeException('This account is already active.');
        }
    }
}
