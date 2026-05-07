<?php

use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminSelectOptionController;
use App\Http\Controllers\Api\V1\OptionGroupController;
use App\Http\Controllers\Api\V1\SelectOptionController;
use App\Http\Controllers\Api\V1\PublicSettingController;
use App\Http\Controllers\Api\V1\PublicPageController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\AdminPhotoModerationController;
use App\Http\Controllers\Api\V1\Admin\AdminReportController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\BlockController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProfileViewController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\CallController;
use App\Http\Controllers\Api\V1\ShortlistController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\ContactMessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — MyBouma Matrimony Platform
| All routes prefixed: /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Public Routes — No Auth Required
    |----------------------------------------------------------------------
    */
    // Dynamic select options (public, cached)
    Route::get('/options/{group}', [SelectOptionController::class, 'index'])->middleware('throttle:120,1');
    Route::get('/option-groups',    [OptionGroupController::class,  'index'])->middleware('throttle:60,1');

    Route::get('/settings', [PublicSettingController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/pages',          [PublicPageController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/pages/{slug}',   [PublicPageController::class, 'show'])->middleware('throttle:60,1');
    Route::post('/contact',       [ContactMessageController::class, 'store'])->middleware('throttle:5,1');

    /*
    |----------------------------------------------------------------------
    | Auth Routes (Public)
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:3,1');        // 3 requests per minute

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');        // 5 requests per minute

        // Password Reset (Public)
        Route::post('/password/forgot', [ForgotPasswordController::class, 'sendLink'])
            ->middleware('throttle:5,1');
        Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
            ->middleware('throttle:5,1');
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');

        // Email Verification (requires valid Sanctum token)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
                ->middleware('throttle:6,1');

            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/change-password', [AuthController::class, 'changePassword'])
                ->middleware('throttle:10,1');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Subscription — Read-only (auth required, email verification NOT needed)
    | Users should see plans and their status even before verifying email.
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->prefix('subscription')->group(function () {
        Route::get('/plans',  [SubscriptionController::class, 'plans']);
        Route::get('/status', [SubscriptionController::class, 'status']);
    });

    /*
    |----------------------------------------------------------------------
    | Protected Routes (Authenticated + Email Verified)
    |----------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'verified.email'])->group(function () {

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::get('/completion', [ProfileController::class, 'completionStatus']);
            Route::post('/photos', [ProfileController::class, 'uploadPhoto']);
            Route::delete('/photos/{photoId}', [ProfileController::class, 'deletePhoto']);
            Route::put('/photos/{photoId}/primary', [ProfileController::class, 'setPrimaryPhoto']);
            Route::get('/{profileId}', [ProfileController::class, 'showById']);
        });

        // Partner Preferences
        Route::put('/preferences', [ProfileController::class, 'updatePreferences']);

        // ---------------------------------------------------------------
        // Phase 2 — Core Features
        // ---------------------------------------------------------------

        // Matches
        Route::prefix('matches')->group(function () {
            Route::get('/', [MatchController::class, 'index']);
            Route::get('/search', [MatchController::class, 'search']);
            Route::get('/{userId}/score', [MatchController::class, 'compatibilityScore']);
        });

        // Interests
        Route::prefix('interests')->group(function () {
            Route::post('/', [InterestController::class, 'send']);
            Route::get('/received', [InterestController::class, 'received']);
            Route::get('/sent', [InterestController::class, 'sent']);
            Route::put('/{id}/accept', [InterestController::class, 'accept']);
            Route::put('/{id}/decline', [InterestController::class, 'decline']);
            Route::put('/{id}/ignore', [InterestController::class, 'ignore']);
        });

        // Shortlist
        Route::post('/shortlist/{userId}', [ShortlistController::class, 'toggle']);
        Route::get('/shortlist', [ShortlistController::class, 'index']);

        // Block
        Route::post('/block/{userId}', [BlockController::class, 'block']);
        Route::delete('/block/{userId}', [BlockController::class, 'unblock']);

        // Report
        Route::post('/report', [ReportController::class, 'report']);

        // Profile Views — requires see_who_viewed_profile
        Route::get('/profile-views', [ProfileViewController::class, 'myViewers']);

        // ---------------------------------------------------------------
        // Phase 3 — Real-time: Chat, Messages, Notifications
        // ---------------------------------------------------------------

        // Conversations
        Route::prefix('conversations')->group(function () {
            Route::get('/', [ChatController::class, 'index']);
            Route::post('/', [ChatController::class, 'getOrCreate'])              // requires chat_access
                ->middleware('feature:chat_access');
            Route::get('/{conversationId}', [ChatController::class, 'show']);

            // Messages within a conversation
            Route::get('/{conversationId}/messages', [MessageController::class, 'index']);
            Route::post('/{conversationId}/messages', [MessageController::class, 'send'])
                ->middleware('feature:chat_access');
            Route::put('/{conversationId}/read', [MessageController::class, 'markRead']);
            Route::post('/{conversationId}/typing', [MessageController::class, 'typing']);
        });

        // Individual message actions
        Route::delete('/messages/{id}', [MessageController::class, 'delete']);

        // ---------------------------------------------------------------
        // Phase 4 — Calls (Auth required + Gold/Platinum subscription)
        // ---------------------------------------------------------------

        Route::prefix('calls')->group(function () {
            Route::post('/initiate', [CallController::class, 'initiate']);
            Route::put('/{id}/answer', [CallController::class, 'answer']);
            Route::put('/{id}/decline', [CallController::class, 'decline']);
            Route::put('/{id}/end', [CallController::class, 'end']);
            Route::post('/{id}/signal', [CallController::class, 'signal']);
            Route::get('/history', [CallController::class, 'history']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::put('/read-all', [NotificationController::class, 'markAllRead']);
            Route::get('/{id}', [NotificationController::class, 'show']);
            Route::put('/{id}/read', [NotificationController::class, 'markRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
        });

        // ---------------------------------------------------------------
        // Phase 5 — Subscription & Payment (SSLCommerz)
        // ---------------------------------------------------------------

        Route::prefix('subscription')->group(function () {
            // plans and status are accessible WITHOUT email verification (above)
            Route::post('/initiate',    [SubscriptionController::class, 'initiate']);
            Route::post('/free',        [SubscriptionController::class, 'subscribeFree']);
            Route::get('/history',      [SubscriptionController::class, 'history']);
            Route::post('/{id}/switch', [SubscriptionController::class, 'switchPlan']);
        });

        // ---------------------------------------------------------------
        // Admin Routes (Authenticated + Admin Role)
        // ---------------------------------------------------------------
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'stats']);
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::put('/users/{id}/ban', [AdminUserController::class, 'ban']);
            Route::put('/users/{id}/verify', [AdminUserController::class, 'verify']);
            Route::get('/photos/pending', [AdminPhotoModerationController::class, 'pending']);
            Route::put('/photos/{id}/approve', [AdminPhotoModerationController::class, 'approve']);
            Route::put('/photos/{id}/reject', [AdminPhotoModerationController::class, 'reject']);
            Route::get('/reports', [AdminReportController::class, 'index']);
            Route::put('/reports/{id}/action', [AdminReportController::class, 'takeAction']);

            // Subscription plan management
            Route::prefix('subscription-plans')->group(function () {
                Route::get('/',       [AdminSubscriptionController::class, 'plans']);
                Route::post('/',      [AdminSubscriptionController::class, 'createPlan']);
                Route::put('/{id}',   [AdminSubscriptionController::class, 'updatePlan']);
                Route::delete('/{id}',[AdminSubscriptionController::class, 'deletePlan']);
            });

            // Subscription sales & revenue
            Route::prefix('subscriptions')->group(function () {
                Route::get('/',       [AdminSubscriptionController::class, 'index']);
                Route::get('/stats',  [AdminSubscriptionController::class, 'stats']);
            });

            // Notification broadcast & history
            Route::prefix('notifications')->group(function () {
                Route::get('/',           [AdminNotificationController::class, 'index']);
                Route::post('/broadcast', [AdminNotificationController::class, 'broadcast']);
            });

            // Dynamic select options management
            Route::prefix('select-options')->group(function () {
                Route::get('/',           [AdminSelectOptionController::class, 'index']);
                Route::get('/groups',     [AdminSelectOptionController::class, 'groups']);
                Route::post('/',          [AdminSelectOptionController::class, 'store']);
                Route::get('/{selectOption}',         [AdminSelectOptionController::class, 'show']);
                Route::put('/{selectOption}',         [AdminSelectOptionController::class, 'update']);
                Route::delete('/{selectOption}',      [AdminSelectOptionController::class, 'destroy']);
                Route::put('/{selectOption}/toggle',  [AdminSelectOptionController::class, 'toggle']);
            });
        });
    });
});

