<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Enorsia Matrimony Platform
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
        Route::get('/users/{userId}', [AdminWebController::class, 'userDetails'])->name('users.show');
        Route::post('/users/{userId}/ban-toggle', [AdminWebController::class, 'toggleUserBan'])->name('users.ban-toggle');
        Route::post('/users/{userId}/face-scan-review', [AdminWebController::class, 'reviewUserFaceScan'])->name('users.face-scan-review');
        Route::get('/users/{userId}/notifications', [AdminWebController::class, 'userNotifications'])->name('users.notifications');

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
        Route::get('/pages/create', [AdminWebController::class, 'createPage'])->name('pages.create');
        Route::post('/pages/store', [AdminWebController::class, 'storePage'])->name('pages.store');
        Route::get('/pages/delete/{id}', [AdminWebController::class, 'deletePage'])->name('pages.delete');
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
        Route::get('/notifications/users-search', [AdminWebController::class, 'usersSearch'])->name('broadcast.users-search');

        // Notification History
        Route::get('/notifications',        [AdminWebController::class, 'notificationHistory'])->name('notifications.history');
        Route::get('/notifications/{id}',   [AdminWebController::class, 'notificationView'])->name('notifications.view');

        // Contact Messages
        Route::get('/contact-messages',             [AdminWebController::class, 'contactMessages'])->name('contact-messages');
        Route::post('/contact-messages/{id}/read',  [AdminWebController::class, 'markMessageRead'])->name('contact-messages.read');
        Route::delete('/contact-messages/{id}',     [AdminWebController::class, 'deleteMessage'])->name('contact-messages.delete');

        // Account — Change Password
        Route::get('/change-password',  [AdminWebController::class, 'changePasswordForm'])->name('change-password');
        Route::post('/change-password', [AdminWebController::class, 'changePassword'])->name('change-password.submit');

        // Select Options (Dynamic Dropdowns)
        Route::prefix('select-options')->name('select-options.')->group(function () {
            Route::get('/',                         [AdminWebController::class, 'selectOptions'])->name('index');
            Route::post('/',                        [AdminWebController::class, 'storeSelectOption'])->name('store');
            Route::get('/{id}/edit',                [AdminWebController::class, 'editSelectOption'])->name('edit');
            Route::put('/{id}',                     [AdminWebController::class, 'updateSelectOption'])->name('update');
            Route::delete('/{id}',                  [AdminWebController::class, 'destroySelectOption'])->name('destroy');
            Route::post('/{id}/toggle',             [AdminWebController::class, 'toggleSelectOption'])->name('toggle');
        });

        // Option Group Configs
        Route::prefix('option-groups')->name('option-groups.')->group(function () {
            Route::get('/',          [AdminWebController::class, 'optionGroups'])->name('index');
            Route::get('/create',    [AdminWebController::class, 'createOptionGroup'])->name('create');
            Route::post('/',         [AdminWebController::class, 'storeOptionGroup'])->name('store');
            Route::get('/{id}/edit', [AdminWebController::class, 'editOptionGroup'])->name('edit');
            Route::put('/{id}',      [AdminWebController::class, 'updateOptionGroup'])->name('update');
            Route::delete('/{id}',   [AdminWebController::class, 'destroyOptionGroup'])->name('destroy');
            Route::post('/{id}/toggle', [AdminWebController::class, 'toggleOptionGroup'])->name('toggle');
        });
    });
});
