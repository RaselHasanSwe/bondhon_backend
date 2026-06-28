<?php

namespace App\Jobs;

use App\Mail\InterestReceivedMailable;
use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInterestReceivedEmail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $interestId,
        public readonly int $sendCount = 1,
    ) {
        $this->delay(
            now()->addSeconds(config('notifications.interest_received_email_delay_seconds', 60))
        );
    }

    public function uniqueId(): string
    {
        return 'interest-received-email-' . $this->interestId . '-' . $this->sendCount;
    }

    public function handle(): void
    {
        $interest = Interest::query()
            ->with([
                'sender.profile',
                'sender.religiousDetail',
                'sender.educationCareer',
                'sender.photos' => fn ($q) => $q
                    ->where('is_approved', true)
                    ->orderByDesc('is_primary'),
                'receiver',
            ])
            ->find($this->interestId);

        if (! $interest) {
            Log::warning('[INTEREST EMAIL] Interest not found. ID: ' . $this->interestId);

            return;
        }

        if ($interest->status !== 'pending') {
            Log::info('[INTEREST EMAIL] Skipped — interest no longer pending. ID: ' . $this->interestId);

            return;
        }

        if ((int) $interest->send_count !== $this->sendCount) {
            Log::info('[INTEREST EMAIL] Skipped — send count mismatch. ID: ' . $this->interestId);

            return;
        }

        $receiver = $interest->receiver;
        $sender   = $interest->sender;

        if (! $receiver || ! $sender) {
            Log::warning('[INTEREST EMAIL] Missing sender or receiver. Interest ID: ' . $this->interestId);

            return;
        }

        if (! $receiver->email || $receiver->is_banned || ! $receiver->is_active) {
            Log::info('[INTEREST EMAIL] Skipped — receiver cannot receive email. User ID: ' . $receiver->id);

            return;
        }

        Log::info('[INTEREST EMAIL] Sending. Interest ID: ' . $this->interestId
            . ' | Send count: ' . $this->sendCount
            . ' | Sender: ' . $sender->id . ' → Receiver: ' . $receiver->id);

        Mail::to($receiver->email)->send(new InterestReceivedMailable($receiver, $sender));
    }
}
