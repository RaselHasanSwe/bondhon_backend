<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\ProfilePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Photo Moderation', description: 'Admin photo approval queue')]
class AdminPhotoModerationController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/admin/photos/pending',
        summary: 'Get photos pending moderation',
        security: [['sanctum' => []]],
        tags: ['Admin - Photo Moderation'],
        responses: [
            new OA\Response(response: 200, description: 'Pending photos list'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function pending(Request $request): JsonResponse
    {
        Log::info('[ADMIN PHOTO - Pending] Request by Admin ID: ' . $request->user()->id);

        try {
            $photos = ProfilePhoto::with('user.profile')
                ->where('moderation_status', 'pending')
                ->orderBy('created_at')
                ->paginate(20);

            Log::info('[ADMIN PHOTO - Pending] Retrieved ' . $photos->total() . ' pending photos for Admin ID: ' . $request->user()->id);

            return $this->successResponse($photos, 'Pending photos retrieved.');

        } catch (\Throwable $e) {
            Log::error('[ADMIN PHOTO - Pending] Failed for Admin ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve pending photos.');
        }
    }

    #[OA\Put(
        path: '/api/v1/admin/photos/{id}/approve',
        summary: 'Approve a photo',
        security: [['sanctum' => []]],
        tags: ['Admin - Photo Moderation'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Photo approved'),
            new OA\Response(response: 404, description: 'Photo not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function approve(Request $request, int $id): JsonResponse
    {
        Log::info('[ADMIN PHOTO - Approve] Admin ID: ' . $request->user()->id . ' | Photo ID: ' . $id);

        try {
            $photo = ProfilePhoto::findOrFail($id);

            DB::transaction(function () use ($photo) {
                $photo->update(['moderation_status' => 'approved', 'is_approved' => true]);
                app(\App\Services\ProfileCompletionService::class)->recalculateAndSave($photo->user);
            });

            Log::info('[ADMIN PHOTO - Approve] Successfully approved Photo ID: ' . $id . ' by Admin: ' . $request->user()->id);

            return $this->successResponse($photo->fresh(), 'Photo approved successfully.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ADMIN PHOTO - Approve] Photo not found. Photo ID: ' . $id);
            return $this->errorResponse('Photo not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[ADMIN PHOTO - Approve] Failed. Admin: ' . $request->user()->id . ' | Photo: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to approve photo.');
        }
    }

    #[OA\Put(
        path: '/api/v1/admin/photos/{id}/reject',
        summary: 'Reject a photo',
        security: [['sanctum' => []]],
        tags: ['Admin - Photo Moderation'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'reason', type: 'string', example: 'Inappropriate content')]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Photo rejected'),
            new OA\Response(response: 404, description: 'Photo not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        Log::info('[ADMIN PHOTO - Reject] Admin ID: ' . $request->user()->id . ' | Photo ID: ' . $id . ' | Reason: ' . ($request->reason ?? 'N/A'));

        try {
            $photo = ProfilePhoto::findOrFail($id);

            DB::transaction(function () use ($photo) {
                $photo->update(['moderation_status' => 'rejected', 'is_approved' => false]);
            });

            Log::info('[ADMIN PHOTO - Reject] Successfully rejected Photo ID: ' . $id . ' by Admin: ' . $request->user()->id);

            return $this->successResponse($photo->fresh(), 'Photo rejected.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ADMIN PHOTO - Reject] Photo not found. Photo ID: ' . $id);
            return $this->errorResponse('Photo not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[ADMIN PHOTO - Reject] Failed. Admin: ' . $request->user()->id . ' | Photo: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to reject photo.');
        }
    }
}
