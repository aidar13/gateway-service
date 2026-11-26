<?php

declare(strict_types=1);

use App\Http\Controllers\ProxyController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;

// Stateless routes outside the /api prefix: token endpoint and the reverse proxy.
Route::post('oauth/token', [AccessTokenController::class, 'issueToken'])
    ->middleware('throttle:auth')
    ->name('passport.token');

Route::any('gateway/{service}/{path}', ProxyController::class)
    ->where('path', '.*')
    ->middleware('throttle:gateway');
