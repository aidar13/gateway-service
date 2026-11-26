<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('code', [OtpController::class, 'store']);
        Route::post('code-repeat', [OtpController::class, 'repeat']);
    });

    Route::middleware(['auth:api', EnsureUserIsActive::class])->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('admin')
    ->middleware(['auth:api', EnsureUserIsActive::class, 'permission:gateway.manage'])
    ->group(function (): void {
        Route::get('services', [ServiceController::class, 'index']);
        Route::post('services', [ServiceController::class, 'store']);
        Route::get('services/{id}', [ServiceController::class, 'show'])->whereNumber('id');
        Route::post('services/{id}/import-routes', [ServiceController::class, 'importRoutes'])->whereNumber('id');
        Route::put('users/{id}/roles', UserRoleController::class)->whereNumber('id');
    });
