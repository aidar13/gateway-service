<?php

declare(strict_types=1);

use App\Enums\SystemPermission;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\ScopeBindingController;
use App\Http\Controllers\Api\ScopeController;
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

// Admin API: every area has its own permission, so duties can be split between roles.
Route::prefix('admin')
    ->middleware(['auth:api', EnsureUserIsActive::class])
    ->where(['id' => '[0-9]+', 'serviceId' => '[0-9]+', 'routeId' => '[0-9]+'])
    ->group(function (): void {
        $can = static fn (SystemPermission $permission): string => 'permission:'.$permission->value;

        Route::middleware($can(SystemPermission::ServicesManage))->group(function (): void {
            Route::apiResource('services', ServiceController::class)->parameters(['services' => 'id']);
            Route::post('services/{id}/import-routes', [ServiceController::class, 'importRoutes']);
            Route::get('routes', [RouteController::class, 'index']);
        });

        Route::middleware($can(SystemPermission::AccessManage))->group(function (): void {
            Route::apiResource('roles', RoleController::class)->parameters(['roles' => 'id']);
            Route::apiResource('permissions', PermissionController::class)->parameters(['permissions' => 'id']);
            Route::put('users/{id}/roles', UserRoleController::class);
        });

        Route::middleware($can(SystemPermission::ScopesManage))->group(function (): void {
            Route::apiResource('scopes', ScopeController::class)->parameters(['scopes' => 'id']);
            Route::put('scopes/{id}/services/{serviceId}', [ScopeBindingController::class, 'bindService']);
            Route::delete('scopes/{id}/services/{serviceId}', [ScopeBindingController::class, 'unbindService']);
            Route::put('scopes/{id}/routes/{routeId}', [ScopeBindingController::class, 'bindRoute']);
            Route::delete('scopes/{id}/routes/{routeId}', [ScopeBindingController::class, 'unbindRoute']);
        });

        Route::middleware($can(SystemPermission::ClientsManage))->group(function (): void {
            Route::apiResource('clients', ClientController::class)->except('update')->parameters(['clients' => 'id']);
            Route::put('clients/{id}/scopes', [ClientController::class, 'syncScopes']);
        });
    });
