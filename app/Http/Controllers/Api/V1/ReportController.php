<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Report\ReportUserRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReportController extends ApiController
{
    /**
     * POST /api/v1/report/{userId}
     * Report a user for a specific reason.
     */
    public function report(ReportUserRequest $request): JsonResponse
    {
        $reporter   = $request->user();
        $reportedId = $request->validated()['reported_id'];

        Log::info('[REPORT - Report] Reporter: ' . $reporter->id . ' | Reported: ' . $reportedId);

        if ($reporter->id === $reportedId) {
            return $this->errorResponse('You cannot report yourself.', null, 422);
        }

        // Prevent duplicate active reports
        $existingReport = Report::where('reporter_id', $reporter->id)
            ->where('reported_id', $reportedId)
            ->whereIn('status', ['pending', 'reviewed'])
            ->first();

        if ($existingReport) {
            return $this->errorResponse('You have already reported this user. Your report is under review.', null, 422);
        }

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reported_id' => $reportedId,
            'reason'      => $request->validated()['reason'],
            'description' => $request->validated()['description'] ?? null,
            'status'      => 'pending',
        ]);

        // Auto-flag if 3+ reports on reported user
        $reportCount = Report::where('reported_id', $reportedId)
            ->whereIn('status', ['pending', 'reviewed'])
            ->count();

        if ($reportCount >= 3) {
            Log::warning('[REPORT - AutoFlag] User ID: ' . $reportedId . ' has ' . $reportCount . ' reports. Flagged for priority review.');
            // Mark the reported user for review (could trigger admin notification here in Phase 3)
        }

        Log::info('[REPORT - Report] Success. Report ID: ' . $report->id . ' | Reporter: ' . $reporter->id . ' | Reported: ' . $reportedId);

        return $this->successResponse(['id' => $report->id], 'Report submitted successfully. We will review it shortly.', 201);
    }
}

