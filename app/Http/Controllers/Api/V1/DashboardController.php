<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\MatchResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends ApiController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * GET /api/v1/dashboard
     * Aggregated dashboard payload (stats, completion, top matches).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[DASHBOARD - Index] User ID: ' . $user->id);

        $summary = $this->dashboardService->getSummary($user);

        $matchesPayload = MatchResource::collection($summary['matches'])
            ->response()
            ->getData(true);

        return $this->successResponse([
            'completion' => $summary['completion'],
            'stats'      => $summary['stats'],
            'matches'    => $matchesPayload['data'] ?? [],
            'matches_total' => $summary['matches']->total(),
        ], 'Dashboard summary retrieved.');
    }
}
