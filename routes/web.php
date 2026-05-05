<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Bondhon Matrimony Platform
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect('/super-admin/login'));

/*
|--------------------------------------------------------------------------
| SSLCommerz Payment Callbacks
| Note: CSRF is excluded for 'payment/*' in bootstrap/app.php
|--------------------------------------------------------------------------
*/
Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/success', [PaymentController::class, 'success'])->name('success');
    Route::post('/fail',    [PaymentController::class, 'fail'])->name('fail');
    Route::post('/cancel',  [PaymentController::class, 'cancel'])->name('cancel');
    Route::post('/ipn',     [PaymentController::class, 'ipn'])->name('ipn');
});

/*
|--------------------------------------------------------------------------
| Super Admin Panel (Blade / Session Auth)
|--------------------------------------------------------------------------
*/
Route::prefix('super-admin')->name('admin.web.')->group(function () {

    // Auth (public within this group)
    Route::get('/login',  [AdminWebController::class, 'loginForm'])->name('login');
    Route::post('/login', [AdminWebController::class, 'login'])->name('login.submit');

    // Protected admin routes
    Route::middleware('admin.web')->group(function () {
        Route::post('/logout', [AdminWebController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');

        // Users
        Route::get('/users', [AdminWebController::class, 'users'])->name('users');

        // Subscription Plans (CRUD)
        Route::get('/subscription-plans',          [AdminWebController::class, 'plans'])->name('plans');
        Route::post('/subscription-plans',         [AdminWebController::class, 'createPlan'])->name('plans.store');
        Route::get('/subscription-plans/{id}/edit',[AdminWebController::class, 'editPlan'])->name('plans.edit');
        Route::put('/subscription-plans/{id}',     [AdminWebController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/subscription-plans/{id}',  [AdminWebController::class, 'deletePlan'])->name('plans.destroy');

        // Subscriptions Sales
        Route::get('/subscriptions', [AdminWebController::class, 'subscriptions'])->name('subscriptions');

        // Site Settings
        Route::get('/settings',  [AdminWebController::class, 'settings'])->name('settings');
        Route::post('/settings', [AdminWebController::class, 'updateSettings'])->name('settings.update');

        // Pages (CMS)
        Route::get('/pages', [AdminWebController::class, 'pages'])->name('pages');
        Route::get('/pages/{id}/edit', [AdminWebController::class, 'editPage'])->name('pages.edit');
        Route::put('/pages/{id}', [AdminWebController::class, 'updatePage'])->name('pages.update');

        // Photo Moderation
        Route::get('/photos', [AdminWebController::class, 'photos'])->name('photos');
        Route::post('/photos/{id}/action', [AdminWebController::class, 'photoAction'])->name('photos.action');

        // Reports
        Route::get('/reports', [AdminWebController::class, 'reports'])->name('reports');
        Route::post('/reports/{id}/dismiss', [AdminWebController::class, 'dismissReport'])->name('reports.dismiss');
        Route::post('/reports/{id}/ban', [AdminWebController::class, 'banUserFromReport'])->name('reports.ban');

        // Broadcast Notifications
        Route::get('/notifications/broadcast',  [AdminWebController::class, 'broadcastForm'])->name('broadcast');
        Route::post('/notifications/broadcast', [AdminWebController::class, 'sendBroadcast'])->name('broadcast.send');
    });
});
