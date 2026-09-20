<?php

use App\Http\Controllers\CspReportController;
use App\Http\Controllers\InstagramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/csp-reports', [CspReportController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('csp.report');

Route::get('/webhooks/meta/instagram', [InstagramWebhookController::class, 'verify'])
    ->name('webhooks.instagram.verify');

Route::post('/webhooks/meta/instagram', [InstagramWebhookController::class, 'receive'])
    ->name('webhooks.instagram.receive');
