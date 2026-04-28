<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly User $user,
        public readonly string $type,
        public readonly array $data = []
    ) {}

    public function handle(): void
    {
        Log::info('[EMAIL NOTIFICATION - Send] Type: ' . $this->type . ' | User: ' . $this->user->id);

        // TODO: Phase 3 — Implement Mailable classes per notification type
        // Match $this->type and dispatch appropriate Mailable:
        // 'interest_received', 'match_digest', 'message_alert', etc.
    }
}

