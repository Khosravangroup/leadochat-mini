<?php

use App\Http\Controllers\InstagramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/meta/instagram', [InstagramWebhookController::class, 'verify'])
    ->name('webhooks.instagram.verify');

Route::post('/webhooks/meta/instagram', [InstagramWebhookController::class, 'receive'])
    ->name('webhooks.instagram.receive');
