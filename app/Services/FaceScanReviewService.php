<?php

namespace App\Services;

use App\Models\FaceScanCapture;
use App\Models\FaceScanSession;
use App\Models\User;
use App\Notifications\FaceScanApprovedNotification;
use App\Notifications\FaceScanRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FaceScanReviewService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly UserBanService $banService,
    ) {}

    public function review(
        User $user,
        FaceScanSession $session,
        string $decision,
        ?string $reviewNote,
        int $reviewedBy,
        bool $sendEmail = false,
    ): void {
        DB::transaction(function () use ($user, $session, $decision, $reviewNote, $reviewedBy, $sendEmail) {
            $finalStatus = $decision === 'ban' ? 'rejected' : $decision;
            $reason = $reviewNote ?? '';

            if ($decision === 'rejected') {
                $this->archiveCurrentCaptures($session, $finalStatus, $reason, $reviewedBy);
                $session->refresh();
            }

            $metadata = $session->metadata ?? [];
            $history = $metadata['review_history'] ?? [];
            $history[] = [
                'decision' => $finalStatus,
                'reason' => $reason,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now()->toIso8601String(),
            ];
            $metadata['review_history'] = $history;

            $session->update([
                'status' => $finalStatus,
                'review_note' => $reason ?: null,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
                'completed_at' => $decision === 'rejected' ? null : $session->completed_at,
                'metadata' => $metadata,
            ]);

            if ($decision === 'approved') {
                $user->update(['is_active' => true]);
                $this->notificationService->notifyFaceScanApproved($user);
                Notification::send($user, new FaceScanApprovedNotification());
            }

            if ($decision === 'rejected') {
                $user->update(['is_active' => false]);
                $user->tokens()->delete();

                $this->notificationService->notifyFaceScanRejected($user, $reason);
                if ($sendEmail && $reason) {
                    Notification::send($user, new FaceScanRejectedNotification($reason));
                }
            }

            if ($decision === 'ban') {
                $this->banService->ban(
                    $user,
                    $reason ?: 'Account banned following face scan review.',
                    false,
                );
            }
        });
    }

    /**
     * Archive capture records into session metadata and remove DB rows.
     * Image files on disk are kept for admin review history.
     */
    private function archiveCurrentCaptures(
        FaceScanSession $session,
        string $decision,
        string $reason,
        int $reviewedBy,
    ): void {
        $session->loadMissing('captures');

        if ($session->captures->isEmpty()) {
            return;
        }

        $metadata = $session->metadata ?? [];
        $archived = $metadata['archived_submissions'] ?? [];

        $archived[] = [
            'decision' => $decision,
            'reason' => $reason,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now()->toIso8601String(),
            'captures' => $session->captures->map(fn (FaceScanCapture $capture) => [
                'capture_key' => $capture->capture_key,
                'image_path' => $capture->image_path,
                'captured_at' => $capture->captured_at?->toIso8601String(),
            ])->values()->all(),
        ];

        $metadata['archived_submissions'] = $archived;

        $session->captures()->delete();

        $session->update([
            'metadata' => $metadata,
            'completed_at' => null,
        ]);
    }
}
