<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Seller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Silvernoise API Routes
|--------------------------------------------------------------------------
|
| Public:  /api/auth/*
| Seller:  /api/seller/*  — requires auth + active seller account
| Admin:   /api/admin/*   — requires auth + admin/finance role
|
*/

// ── Auth (public) ─────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('login',           [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password',  [AuthController::class, 'resetPassword']);
    Route::get('activate/{token}', [AuthController::class, 'activate'])->name('auth.activate');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',   [AuthController::class, 'logout']);
        Route::get('me',        [AuthController::class, 'me']);
        Route::put('me',        [AuthController::class, 'updateProfile']);
        Route::put('password',  [AuthController::class, 'changePassword']);
    });
});

// ── Seller Central ────────────────────────────────────────────────────────────

Route::prefix('seller')
    ->middleware(['auth:sanctum', 'seller.active'])
    ->group(function () {

    // Dashboard
    Route::get('dashboard',         [Seller\DashboardController::class, 'index']);

    // Releases (read-only for sellers)
    Route::get('releases',          [Seller\ReleaseController::class, 'index']);
    Route::get('releases/{id}',     [Seller\ReleaseController::class, 'show']);

    // Reports
    Route::get('reports',           [Seller\ReportController::class, 'index']);
    Route::get('reports/{id}',      [Seller\ReportController::class, 'show']);
    Route::get('reports/{id}/download', [Seller\ReportController::class, 'download']);

    // Payment requests
    Route::get('payments',          [Seller\PaymentController::class, 'index']);
    Route::post('payments',         [Seller\PaymentController::class, 'store']);
    Route::get('payments/{id}',     [Seller\PaymentController::class, 'show']);
});

// ── Admin Central ─────────────────────────────────────────────────────────────

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin.role'])
    ->group(function () {

    // Dashboard stats
    Route::get('dashboard',         [Admin\DashboardController::class, 'index']);

    // Customers
    Route::get('customers',         [Admin\CustomerController::class, 'index']);
    Route::post('customers',        [Admin\CustomerController::class, 'store']);
    Route::get('customers/{id}',    [Admin\CustomerController::class, 'show']);
    Route::put('customers/{id}',    [Admin\CustomerController::class, 'update']);
    Route::post('customers/{id}/activate',   [Admin\CustomerController::class, 'activate']);
    Route::post('customers/{id}/deactivate', [Admin\CustomerController::class, 'deactivate']);
    Route::post('customers/{id}/block',      [Admin\CustomerController::class, 'block']);
    Route::post('customers/{id}/reset-password', [Admin\CustomerController::class, 'resetPassword']);
    Route::post('customers/{id}/feature',    [Admin\CustomerController::class, 'toggleFeatured']);

    // Labels
    Route::get('labels',            [Admin\LabelController::class, 'index']);
    Route::post('labels',           [Admin\LabelController::class, 'store']);
    Route::get('labels/{id}',       [Admin\LabelController::class, 'show']);
    Route::put('labels/{id}',       [Admin\LabelController::class, 'update']);
    Route::post('labels/{id}/assign', [Admin\LabelController::class, 'assignCustomer']);

    // Releases
    Route::get('releases',          [Admin\ReleaseController::class, 'index']);
    Route::post('releases',         [Admin\ReleaseController::class, 'store']);
    Route::get('releases/{id}',     [Admin\ReleaseController::class, 'show']);
    Route::put('releases/{id}',     [Admin\ReleaseController::class, 'update']);
    Route::put('releases/{id}/status', [Admin\ReleaseController::class, 'updateStatus']);

    // Tracks
    Route::get('releases/{releaseId}/tracks',     [Admin\TrackController::class, 'index']);
    Route::post('releases/{releaseId}/tracks',    [Admin\TrackController::class, 'store']);
    Route::get('tracks/{id}',                     [Admin\TrackController::class, 'show']);
    Route::put('tracks/{id}',                     [Admin\TrackController::class, 'update']);
    Route::post('tracks/{id}/audio',              [Admin\TrackController::class, 'uploadAudio']);

    // Reports
    Route::get('reports',           [Admin\ReportController::class, 'index']);
    Route::post('reports',          [Admin\ReportController::class, 'store']);
    Route::get('reports/{id}',      [Admin\ReportController::class, 'show']);
    Route::put('reports/{id}',      [Admin\ReportController::class, 'update']);
    Route::delete('reports/{id}',   [Admin\ReportController::class, 'destroy']);

    // Payments
    Route::get('payments',          [Admin\PaymentController::class, 'index']);
    Route::get('payments/{id}',     [Admin\PaymentController::class, 'show']);
    Route::put('payments/{id}/status', [Admin\PaymentController::class, 'updateStatus']);

    // Email templates
    Route::get('email-templates',          [Admin\EmailTemplateController::class, 'index']);
    Route::get('email-templates/{key}',    [Admin\EmailTemplateController::class, 'show']);
    Route::put('email-templates/{key}',    [Admin\EmailTemplateController::class, 'update']);
    Route::post('email-templates/{key}/test', [Admin\EmailTemplateController::class, 'sendTest']);

    // Newsletter
    Route::get('newsletters',              [Admin\NewsletterController::class, 'index']);
    Route::post('newsletters',             [Admin\NewsletterController::class, 'store']);
    Route::get('newsletters/{id}',         [Admin\NewsletterController::class, 'show']);
    Route::put('newsletters/{id}',         [Admin\NewsletterController::class, 'update']);
    Route::post('newsletters/{id}/send',   [Admin\NewsletterController::class, 'send']);
    Route::post('newsletters/{id}/schedule', [Admin\NewsletterController::class, 'schedule']);

    // SMTP settings
    Route::get('smtp',              [Admin\SmtpController::class, 'show']);
    Route::put('smtp',              [Admin\SmtpController::class, 'update']);
    Route::post('smtp/test',        [Admin\SmtpController::class, 'test']);

    // Email log
    Route::get('email-log',         [Admin\EmailLogController::class, 'index']);

    // API / KVZ logs
    Route::get('api-logs',          [Admin\ApiLogController::class, 'index']);
    Route::post('kvz/sync',         [Admin\ApiLogController::class, 'triggerKvzSync']);

    // Reference data
    Route::get('genres',            [Admin\ReferenceController::class, 'genres']);
    Route::get('stores',            [Admin\ReferenceController::class, 'stores']);
    Route::get('artists',           [Admin\ReferenceController::class, 'artists']);
});
