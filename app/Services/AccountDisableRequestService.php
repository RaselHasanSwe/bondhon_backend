<?php

namespace App\Services;

use App\Enums\AccountDisableAdminAction;
use App\Enums\AccountDisableRequestEmailType;
use App\Enums\AccountDisableRequestStatus;
use App\Enums\AccountDisableRequestType;
use App\Jobs\SendAccountDisableRequestEmail;
use App\Models\AccountDisableRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AccountDisableRequestService
{
    public function __construct(
        private readonly UserBanService $banService,
        private readonly NotificationService $notificationService,
    ) {}

    public function submit(User $user, AccountDisableRequestType $requestType, string $message): AccountDisableRequest
    {
        $hasPending = AccountDisableRequest::query()
            ->where('user_id', $user->id)
            ->pending()
            ->exists();

        if ($hasPending) {
            throw new RuntimeException('You already have a pending account disable request under review.');
        }

        $request = AccountDisableRequest::create([
            'user_id'      => $user->id,
            'request_type' => $requestType,
            'message'      => $message,
            'status'       => AccountDisableRequestStatus::Pending,
        ]);

        Log::info('[ACCOUNT DISABLE REQUEST] Submitted', [
            'request_id'   => $request->id,
            'user_id'      => $user->id,
            'request_type' => $requestType->value,
        ]);

        $this->notificationService->notifyAccountDisableRequestSubmitted($user, $requestType->label());
        SendAccountDisableRequestEmail::dispatch($request->id, AccountDisableRequestEmailType::Submitted);

        return $request;
    }

    public function disableAccount(AccountDisableRequest $request, User $admin, string $adminMessage): void
    {
        $this->assertPending($request);

        DB::transaction(function () use ($request, $admin, $adminMessage) {
            $user = $request->user()->lockForUpdate()->firstOrFail();

            $this->banService->disable($user, $adminMessage);

            $request->update([
                'status'        => AccountDisableRequestStatus::ActionTaken,
                'admin_action'  => AccountDisableAdminAction::Disabled,
                'admin_message' => $adminMessage,
                'reviewed_by'   => $admin->id,
                'reviewed_at'   => now(),
            ]);
        });

        $request->refresh();
        $user = $request->user;

        $this->notificationService->notifyAccountDisableRequestDisabled($user, $adminMessage);
        SendAccountDisableRequestEmail::dispatch($request->id, AccountDisableRequestEmailType::Disabled);

        Log::info('[ACCOUNT DISABLE REQUEST] Account disabled', [
            'request_id' => $request->id,
            'user_id'    => $request->user_id,
            'admin_id'   => $admin->id,
        ]);
    }

    public function banAccount(AccountDisableRequest $request, User $admin, string $adminMessage): void
    {
        $this->assertPending($request);

        DB::transaction(function () use ($request, $admin, $adminMessage) {
            $user = $request->user()->lockForUpdate()->firstOrFail();

            $this->banService->ban($user, $adminMessage, false);

            $request->update([
                'status'        => AccountDisableRequestStatus::ActionTaken,
                'admin_action'  => AccountDisableAdminAction::Banned,
                'admin_message' => $adminMessage,
                'reviewed_by'   => $admin->id,
                'reviewed_at'   => now(),
            ]);
        });

        $request->refresh();
        $user = $request->user;

        $this->notificationService->notifyAccountDisableRequestBanned($user, $adminMessage);
        SendAccountDisableRequestEmail::dispatch($request->id, AccountDisableRequestEmailType::Banned);

        Log::info('[ACCOUNT DISABLE REQUEST] Account banned', [
            'request_id' => $request->id,
            'user_id'    => $request->user_id,
            'admin_id'   => $admin->id,
        ]);
    }

    public function dismiss(AccountDisableRequest $request, User $admin, ?string $adminMessage = null): void
    {
        $this->assertPending($request);

        $request->update([
            'status'        => AccountDisableRequestStatus::Dismissed,
            'admin_message' => $adminMessage,
            'reviewed_by'   => $admin->id,
            'reviewed_at'   => now(),
        ]);

        $request->refresh();
        $user = $request->user;

        $this->notificationService->notifyAccountDisableRequestDismissed($user, $adminMessage);
        SendAccountDisableRequestEmail::dispatch($request->id, AccountDisableRequestEmailType::Dismissed);

        Log::info('[ACCOUNT DISABLE REQUEST] Dismissed', [
            'request_id' => $request->id,
            'user_id'    => $request->user_id,
            'admin_id'   => $admin->id,
        ]);
    }

    public function reactivateAccount(AccountDisableRequest $request, User $admin, ?string $adminMessage = null): void
    {
        if (! $request->canReactivate()) {
            throw new RuntimeException('This request cannot be reactivated.');
        }

        if ($request->user_id === $admin->id) {
            throw new RuntimeException('You cannot reactivate your own account.');
        }

        DB::transaction(function () use ($request, $admin, $adminMessage) {
            $user = $request->user()->lockForUpdate()->firstOrFail();

            $this->banService->reactivate($user);

            $request->update([
                'admin_action'  => AccountDisableAdminAction::Reactivated,
                'admin_message' => $adminMessage,
                'reviewed_by'   => $admin->id,
                'reviewed_at'   => now(),
            ]);
        });

        $request->refresh();
        $user = $request->user;

        $this->notificationService->notifyAccountDisableRequestReactivated($user, $adminMessage);
        SendAccountDisableRequestEmail::dispatch($request->id, AccountDisableRequestEmailType::Reactivated);

        Log::info('[ACCOUNT DISABLE REQUEST] Account reactivated', [
            'request_id' => $request->id,
            'user_id'    => $request->user_id,
            'admin_id'   => $admin->id,
        ]);
    }

    private function assertPending(AccountDisableRequest $request): void
    {
        if (! $request->isPending()) {
            throw new RuntimeException('This request has already been reviewed.');
        }
    }
}
