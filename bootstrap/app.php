<?php

use App\Http\Middleware\AdminWebAuth;
use App\Http\Middleware\CheckFeature;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureProfileIsComplete;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\OptionalSanctumAuth;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // Exclude SSLCommerz payment callbacks from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'payment/*',
        ]);

        $middleware->alias([
            'verified.email'   => EnsureEmailIsVerified::class,
            'profile.complete' => EnsureProfileIsComplete::class,
            'subscription'     => CheckSubscription::class,
            'feature'          => CheckFeature::class,
            'admin'            => EnsureUserIsAdmin::class,
            'admin.web'        => AdminWebAuth::class,
            'auth.optional'    => OptionalSanctumAuth::class,
        ]);
        // Auto-update last_seen_at on every auth API request
        $middleware->appendToGroup('api', UpdateLastSeen::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Unauthenticated.',
                    'errors'  => null,
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Resource not found.',
                    'errors'  => null,
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Endpoint not found.',
                    'errors'  => null,
                ], 404);
            }
        });
    })->create();
