<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\FaceScanCapture;
use App\Models\FaceScanSession;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Authentication', description: 'User registration, login, and session management')]
class FaceScanController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/auth/face-scan/status',
        summary: 'Get the authenticated user face scan status',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Face scan status retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $session = $user->faceScanSession()->with(['captures', 'latestCapture'])->first();

        return $this->successResponse([
            'face_scan_required' => SiteSetting::booleanValue('face_scan_enabled', true),
            'session' => $this->formatSession($session),
        ], 'Face scan status retrieved successfully.');
    }

    #[OA\Post(
        path: '/api/v1/auth/face-scan/captures',
        summary: 'Upload one face scan capture',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'capture', type: 'string', format: 'binary'),
                        new OA\Property(property: 'capture_key', type: 'string', example: 'front'),
                        new OA\Property(property: 'capture_type', type: 'string', example: 'front'),
                        new OA\Property(property: 'has_glasses', type: 'boolean', example: false),
                        new OA\Property(property: 'expression', type: 'string', example: 'neutral'),
                        new OA\Property(property: 'confidence', type: 'number', format: 'float', example: 0.98),
                        new OA\Property(property: 'face_turn', type: 'string', example: 'front'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Capture stored'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function storeCapture(Request $request): JsonResponse
    {
        Log::info('[FACE SCAN - Capture] Attempting to store capture for User ID: ' . $request->user()->id);
        Log::info('[FACE SCAN - Capture] Request Data', [
            'capture' => $request->input('capture'),
            'capture_key' => $request->input('capture_key'),
            'capture_type' => $request->input('capture_type'),
            'has_glasses' => $request->boolean('has_glasses', false),
            'expression' => $request->input('expression'),
            'confidence' => $request->input('confidence'),
            'face_turn' => $request->input('face_turn'),
        ]);

        $request->validate([
            'capture' => ['required', 'image', 'mimes:jpg,jpeg,png,webp'],
            'capture_key' => ['required', 'string', 'max:50'],
            'capture_type' => ['nullable', 'string', 'max:50'],
            'has_glasses' => ['nullable', 'boolean'],
            'expression' => ['nullable', 'string', 'max:50'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'face_turn' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $session = FaceScanSession::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'pending']
        );

        try {
            $image = Image::read($request->file('capture'));
            $image->scaleDown(width: 1200);

            $captureKey = $request->string('capture_key')->toString();
            $filename = sprintf('face-scans/%d/%d/%s_%s.jpg', $user->id, $session->id, $captureKey, uniqid());
            Storage::disk('public')->put($filename, $image->toJpeg(88));

            FaceScanCapture::updateOrCreate(
                [
                    'face_scan_session_id' => $session->id,
                    'capture_key' => $captureKey,
                ],
                [
                    'user_id' => $user->id,
                    'image_path' => $filename,
                    'metadata' => [
                        'capture_type' => $request->input('capture_type'),
                        'has_glasses' => $request->boolean('has_glasses', false),
                        'expression' => $request->input('expression'),
                        'confidence' => $request->input('confidence'),
                        'face_turn' => $request->input('face_turn'),
                    ],
                    'captured_at' => now(),
                ]
            );

            $session->refresh();
            $this->autoSubmitIfReady($session);

            Log::info('[FACE SCAN - Capture] Stored capture for User ID: ' . $user->id . ' | Session ID: ' . $session->id . ' | Key: ' . $captureKey);

            return $this->successResponse([
                'session' => $this->formatSession($session->fresh(['captures', 'latestCapture'])),
                'required_capture_keys' => ['front', 'left', 'right', 'smile', 'down', 'up'],
            ], 'Face capture stored successfully.', 201);
        } catch (\Throwable $e) {
            Log::error('[FACE SCAN - Capture] Failed for User ID: ' . $user->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->serverErrorResponse('Failed to store face capture. Please try again.');
        }
    }

    private function autoSubmitIfReady(FaceScanSession $session): void
    {
        $session->loadMissing('captures');

        $keys = $session->captures->pluck('capture_key')->values();
        $required = collect(['front', 'left', 'right', 'smile', 'down', 'up']);
        $randomCount = $keys->filter(fn ($key) => str_starts_with((string) $key, 'random'))->count();
        $hasAllRequired = $required->every(fn ($key) => $keys->contains($key));

        if ($session->status === 'pending' && $hasAllRequired && $randomCount >= 2) {
            $session->update([
                'status' => 'submitted',
                'completed_at' => now(),
            ]);
        }
    }

    private function formatSession(?FaceScanSession $session): ?array
    {
        if (! $session) {
            return null;
        }

        return [
            'id' => $session->id,
            'status' => $session->status,
            'completed_at' => $session->completed_at,
            'reviewed_at' => $session->reviewed_at,
            'review_note' => $session->review_note,
            'captures' => $session->captures->map(fn (FaceScanCapture $capture) => [
                'id' => $capture->id,
                'capture_key' => $capture->capture_key,
                'image_url' => Storage::disk('public')->url($capture->image_path),
                'metadata' => $capture->metadata,
                'captured_at' => $capture->captured_at,
            ])->values(),
            'latest_capture' => $session->latestCapture ? [
                'id' => $session->latestCapture->id,
                'capture_key' => $session->latestCapture->capture_key,
                'image_url' => Storage::disk('public')->url($session->latestCapture->image_path),
                'metadata' => $session->latestCapture->metadata,
                'captured_at' => $session->latestCapture->captured_at,
            ] : null,
        ];
    }
}

