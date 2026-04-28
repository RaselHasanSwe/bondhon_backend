<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Reports', description: 'Admin report management')]
class AdminReportController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/admin/reports',
        summary: 'List all user reports',
        security: [['sanctum' => []]],
        tags: ['Admin - Reports'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'reviewed', 'action_taken', 'dismissed'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reports list'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        Log::info('[ADMIN REPORT - Index] Request by Admin ID: ' . $request->user()->id);

        try {
            $query = Report::with(['reporter.profile', 'reported.profile'])
                ->orderByDesc('created_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $reports = $query->paginate(20);

            Log::info('[ADMIN REPORT - Index] Retrieved ' . $reports->total() . ' reports for Admin ID: ' . $request->user()->id);

            return $this->successResponse($reports, 'Reports retrieved.');

        } catch (\Throwable $e) {
            Log::error('[ADMIN REPORT - Index] Failed for Admin ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve reports.');
        }
    }

    #[OA\Put(
        path: '/api/v1/admin/reports/{id}/action',
        summary: 'Take action on a report',
        security: [['sanctum' => []]],
        tags: ['Admin - Reports'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['reviewed', 'action_taken', 'dismissed']),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Report updated'),
            new OA\Response(response: 404, description: 'Report not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function takeAction(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:reviewed,action_taken,dismissed'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        Log::info('[ADMIN REPORT - TakeAction] Admin ID: ' . $request->user()->id . ' | Report ID: ' . $id . ' | Action: ' . $request->status);

        try {
            $report = Report::findOrFail($id);

            DB::transaction(function () use ($report, $request) {
                $report->update(['status' => $request->status]);
            });

            Log::info('[ADMIN REPORT - TakeAction] Successfully updated Report ID: ' . $id . ' to status: ' . $request->status . ' by Admin: ' . $request->user()->id);

            return $this->successResponse($report->fresh(), 'Report status updated to "' . $request->status . '".');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ADMIN REPORT - TakeAction] Report not found. Report ID: ' . $id);
            return $this->errorResponse('Report not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[ADMIN REPORT - TakeAction] Failed. Admin: ' . $request->user()->id . ' | Report: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to update report status.');
        }
    }
}
