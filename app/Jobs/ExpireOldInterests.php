<?php

namespace App\Jobs;

use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ExpireOldInterests — Runs daily.
 * Marks pending interests as 'expired' if their expires_at has passed.
 */
class ExpireOldInterests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct() {}

    public function handle(): void
    {
        Log::info('[EXPIRE INTERESTS - Start] Processing expired interests...');

        $count = Interest::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        Log::info('[EXPIRE INTERESTS - Complete] Expired ' . $count . ' interests.');
    }
}

