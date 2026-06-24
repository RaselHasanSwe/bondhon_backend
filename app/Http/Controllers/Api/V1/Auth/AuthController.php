<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Profile;
use App\Models\FaceScanSession;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ProfileCompletionService;
use App\Services\SubscriptionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Authentication', description: 'User registration, login, and session management')]
class AuthController extends ApiController
{
    public function __construct(
        private readonly ProfileCompletionService $completionService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'Register a new user',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation', 'gender', 'profile_created_by'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Rahim Uddin'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'rahim@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'password123'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                    new OA\Property(property: 'profile_created_by', type: 'string', enum: ['self', 'parents', 'siblings'], example: 'self'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User registered successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        Log::info('[AUTH - Register] Registering new user: ' . $request->email);

        try {
            $emailVerificationEnabled = SiteSetting::booleanValue('email_verification_enabled', true);

            $result = DB::transaction(function () use ($request, $emailVerificationEnabled) {
                $user = User::create([
                    'name'               => $request->name,
                    'email'              => $request->email,
                    'password'           => $request->password,
                    'gender'             => $request->gender,
                    'profile_created_by' => $request->profile_created_by,
                    'is_active'          => 0,
                ]);

                // Auto-generate profile_id (BON-XXXXXX)
                $profileId = 'BON-' . str_pad($user->id, 6, '0', STR_PAD_LEFT);

                Profile::create([
                    'user_id'    => $user->id,
                    'profile_id' => $profileId,
                ]);

                if ($emailVerificationEnabled) {
                    event(new Registered($user));
                }

                // Calculate initial completion percentage
                $this->completionService->recalculateAndSave($user->fresh());

                if (SiteSetting::booleanValue('face_scan_enabled', true)) {
                    FaceScanSession::firstOrCreate(
                        ['user_id' => $user->id],
                        ['status' => 'pending']
                    );
                }

                // Auto-assign free subscription on registration
                $freePlan = SubscriptionPlan::where('price_bdt', 0)
                    ->where('is_active', true)
                    ->first();
                if ($freePlan) {
                    $this->subscriptionService->activateFree($user, $freePlan);
                    $user->refresh();
                }

                $token = $user->createToken('auth_token')->plainTextToken;

                Log::info('[AUTH - Register] Success. User ID: ' . $user->id . ' | Profile ID: ' . $profileId);

                return [
                    'token'      => $token,
                    'token_type' => 'Bearer',
                    'user'       => $this->formatUser($user->load(['profile', 'faceScanSession'])),
                ];
            });

            $message = $emailVerificationEnabled
                ? 'Registration successful. Please verify your email.'
                : 'Registration successful.';

            return $this->successResponse($result, $message, 201);

        } catch (\Throwable $e) {
            Log::error('[AUTH - Register] Failed for email: ' . $request->email . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Registration failed. Please try again.');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login user',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'rahim@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 403, description: 'Account banned or inactive'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        Log::info('[AUTH - Login] Attempt: ' . $request->email);

        try {
            if (! Auth::attempt($request->only('email', 'password'))) {
                Log::warning('[AUTH - Login] Failed attempt for email: ' . $request->email);
                return $this->errorResponse('Invalid credentials. Please check your email and password.', null, 401);
            }

            /** @var User $user */
            $user = Auth::user();

            if ($user->is_banned) {
                Auth::logout();
                Log::warning('[AUTH - Login] Banned user attempted login. User ID: ' . $user->id);
                return response()->json([
                    'success' => false,
                    'data'    => [
                        'status'     => 'banned',
                        'ban_reason' => $user->ban_reason ?? 'No reason provided. Please contact support.',
                    ],
                    'message' => 'Your account has been banned.',
                    'errors'  => null,
                ], 403);
            }

            if (! $user->is_active) {
                Auth::logout();
                Log::warning('[AUTH - Login] Inactive user attempted login. User ID: ' . $user->id);
                return $this->errorResponse('Your account is inactive.', null, 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Update last seen
            $user->profile?->update(['last_seen_at' => now()]);

            Log::info('[AUTH - Login] Success. User ID: ' . $user->id);

            return $this->successResponse([
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => $this->formatUser($user->load(['profile', 'faceScanSession'])),
            ], 'Login successful.');

        } catch (\Throwable $e) {
            Log::error('[AUTH - Login] Unexpected error for email: ' . $request->email . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Login failed. Please try again.');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Logout user (revoke token)',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        Log::info('[AUTH - Logout] User ID: ' . $request->user()->id);

        try {
            $request->user()->currentAccessToken()->delete();

            Log::info('[AUTH - Logout] Token revoked for User ID: ' . $request->user()->id);

            return $this->successResponse(null, 'Logged out successfully.');

        } catch (\Throwable $e) {
            Log::error('[AUTH - Logout] Failed for User ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Logout failed. Please try again.');
        }
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get authenticated user details',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'User details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load([
                'profile', 'religiousDetail', 'familyDetail',
                'educationCareer', 'lifestyle', 'horoscopeDetail', 'partnerPreference', 'faceScanSession',
            ]);

            return $this->successResponse($this->formatUser($user), 'User details retrieved successfully.');

        } catch (\Throwable $e) {
            Log::error('[AUTH - Me] Failed for User ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve user details.');
        }
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password'      => ['required', 'string'],
            'new_password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect.', null, 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        Log::info('[AUTH - ChangePassword] User ID: ' . $user->id . ' changed their password.');

        return $this->successResponse(null, 'Password changed successfully.');
    }

    /**
     * Format user data for API response (never expose password or sensitive fields).
     */
    private function formatUser(User $user): array
    {
        return [
            'id'                      => $user->id,
            'name'                    => $user->name,
            'email'                   => $user->email,
            'email_verified_at'       => $user->email_verified_at,
            'gender'                  => $user->gender,
            'profile_created_by'      => $user->profile_created_by,
            'role'                    => $user->role,
            'is_active'               => $user->is_active,
            'subscription_plan'       => $user->subscription_plan,
            'subscription_expires_at' => $user->subscription_expires_at,
            'profile'                 => $user->profile,
            'email_verification_required' => SiteSetting::booleanValue('email_verification_enabled', true),
            'face_scan_required'          => SiteSetting::booleanValue('face_scan_enabled', true),
            'face_scan_status'        => $user->faceScanSession?->status,
            'face_scan_completed_at'  => $user->faceScanSession?->completed_at,
            'created_at'              => $user->created_at,
        ];
    }
}

