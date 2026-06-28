<?php

namespace App\Jobs;

use App\Enums\AccountDisableRequestEmailType;
use App\Enums\AccountDisableRequestStatus;
use App\Mail\AccountDisableRequestMailable;
use App\Models\AccountDisableRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAccountDisableRequestEmail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $requestId,
        public readonly AccountDisableRequestEmailType $emailType,
    ) {
        $this->delay(
            now()->addSeconds(config('notifications.account_disable_request_email_delay_seconds', 60))
        );
    }

    public function uniqueId(): string
    {
        return 'account-disable-request-email-' . $this->requestId . '-' . $this->emailType->value;
    }

    public function handle(): void
    {
        $request = AccountDisableRequest::query()
            ->with('user')
            ->find($this->requestId);

        if (! $request) {
            Log::warning('[ACCOUNT DISABLE EMAIL] Request not found. ID: ' . $this->requestId);

            return;
        }

        if (! $this->shouldSendForStatus($request)) {
            Log::info('[ACCOUNT DISABLE EMAIL] Skipped — status mismatch. ID: ' . $this->requestId
                . ' | Email type: ' . $this->emailType->value);

            return;
        }

        $user = $request->user;

        if (! $user || ! $user->email) {
            Log::warning('[ACCOUNT DISABLE EMAIL] Missing user or email. Request ID: ' . $this->requestId);

            return;
        }

        if ($this->emailType === AccountDisableRequestEmailType::Disabled && $user->is_banned) {
            Log::info('[ACCOUNT DISABLE EMAIL] Skipped disabled email — user is banned. User ID: ' . $user->id);

            return;
        }

        Log::info('[ACCOUNT DISABLE EMAIL] Sending. Request ID: ' . $this->requestId
            . ' | Type: ' . $this->emailType->value
            . ' | User: ' . $user->id);

        Mail::to($user->email)->send(new AccountDisableRequestMailable(
            $user,
            $this->emailType,
            $request->admin_message,
            $request->request_type?->label(),
        ));
    }

    private function shouldSendForStatus(AccountDisableRequest $request): bool
    {
        return match ($this->emailType) {
            AccountDisableRequestEmailType::Submitted => $request->status === AccountDisableRequestStatus::Pending,
            AccountDisableRequestEmailType::Disabled  => $request->status === AccountDisableRequestStatus::ActionTaken
                && $request->admin_action?->value === 'disabled',
            AccountDisableRequestEmailType::Banned    => $request->status === AccountDisableRequestStatus::ActionTaken
                && $request->admin_action?->value === 'banned',
            AccountDisableRequestEmailType::Dismissed => $request->status === AccountDisableRequestStatus::Dismissed,
            AccountDisableRequestEmailType::Reactivated => $request->status === AccountDisableRequestStatus::ActionTaken
                && $request->admin_action?->value === 'reactivated',
        };
    }
}
