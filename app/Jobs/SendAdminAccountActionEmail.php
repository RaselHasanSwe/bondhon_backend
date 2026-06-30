<?php

namespace App\Jobs;

use App\Enums\AdminAccountActionEmailType;
use App\Mail\AdminAccountActionMailable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminAccountActionEmail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $userId,
        public readonly AdminAccountActionEmailType $emailType,
        public readonly int $actionTimestamp,
        public readonly ?string $adminMessage = null,
    ) {
        $this->delay(
            now()->addSeconds(config('notifications.admin_account_action_email_delay_seconds', 60))
        );
    }

    public function uniqueId(): string
    {
        return 'admin-account-action-email-' . $this->userId . '-' . $this->emailType->value . '-' . $this->actionTimestamp;
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user || ! $user->email) {
            Log::warning('[ADMIN ACCOUNT EMAIL] Missing user or email. User ID: ' . $this->userId);

            return;
        }

        if (! $this->matchesExpectedState($user)) {
            Log::info('[ADMIN ACCOUNT EMAIL] Skipped — user state changed. User ID: ' . $this->userId
                . ' | Type: ' . $this->emailType->value);

            return;
        }

        Log::info('[ADMIN ACCOUNT EMAIL] Sending. User ID: ' . $this->userId
            . ' | Type: ' . $this->emailType->value);

        Mail::to($user->email)->send(new AdminAccountActionMailable(
            $user,
            $this->emailType,
            $this->adminMessage,
        ));
    }

    private function matchesExpectedState(User $user): bool
    {
        return match ($this->emailType) {
            AdminAccountActionEmailType::Disabled => ! $user->is_active
                && ! $user->is_banned
                && $user->disabled_at?->timestamp === $this->actionTimestamp,
            AdminAccountActionEmailType::Banned => $user->is_banned
                && $user->banned_at?->timestamp === $this->actionTimestamp,
            AdminAccountActionEmailType::Reactivated => $user->is_active
                && ! $user->is_banned
                && $user->disabled_at === null
                && $user->banned_at === null,
        };
    }
}
