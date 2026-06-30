<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingService;
use Illuminate\Http\JsonResponse;

class PublicSettingController extends Controller
{
    public function __construct(private readonly SiteSettingService $settingService) {}

    /**
     * GET /api/v1/settings
     * Returns all site settings as a key→value map. No auth required.
     */
    public function index(): JsonResponse
    {
        $settings = $this->settingService->all();

        return response()->json([
            'success' => true,
            'data'    => $settings,
            'message' => 'Settings retrieved successfully.',
            'errors'  => [],
        ]);
    }
}
