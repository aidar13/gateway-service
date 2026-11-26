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

// Admin API: every action has its own permission, so duties can be split between roles.
Route::prefix('admin')
    ->middleware(['auth:api', EnsureUserIsActive::class])
    ->where(['id' => '[0-9]+', 'serviceId' => '[0-9]+', 'routeId' => '[0-9]+'])
    ->group(function (): void {
        $can = static fn (SystemPermission $permission): string => 'permission:'.$permission->value;

        Route::apiResource('services', ServiceController::class)->parameters(['services' => 'id'])
            ->middlewareFor(['index', 'show'], $can(SystemPermission::ServicesView))
            ->middlewareFor('store', $can(SystemPermission::ServicesCreate))
            ->middlewareFor('update', $can(SystemPermission::ServicesUpdate))
            ->middlewareFor('destroy', $can(SystemPermission::ServicesDelete));
        Route::post('services/{id}/import-routes', [ServiceController::class, 'importRoutes'])
            ->middleware($can(SystemPermission::ServicesImportRoutes));
        Route::get('routes', [RouteController::class, 'index'])
            ->middleware($can(SystemPermission::RoutesView));

        Route::apiResource('roles', RoleController::class)->parameters(['roles' => 'id'])
            ->middlewareFor(['index', 'show'], $can(SystemPermission::RolesView))
            ->middlewareFor('store', $can(SystemPermission::RolesCreate))
            ->middlewareFor('update', $can(SystemPermission::RolesUpdate))
            ->middlewareFor('destroy', $can(SystemPermission::RolesDelete));
        Route::apiResource('permissions', PermissionController::class)->parameters(['permissions' => 'id'])
            ->middlewareFor(['index', 'show'], $can(SystemPermission::PermissionsView))
            ->middlewareFor('store', $can(SystemPermission::PermissionsCreate))
            ->middlewareFor('update', $can(SystemPermission::PermissionsUpdate))
            ->middlewareFor('destroy', $can(SystemPermission::PermissionsDelete));
        Route::put('users/{id}/roles', UserRoleController::class)
            ->middleware($can(SystemPermission::UsersAssignRoles));

        Route::apiResource('scopes', ScopeController::class)->parameters(['scopes' => 'id'])
            ->middlewareFor(['index', 'show'], $can(SystemPermission::ScopesView))
            ->middlewareFor('store', $can(SystemPermission::ScopesCreate))
            ->middlewareFor('update', $can(SystemPermission::ScopesUpdate))
            ->middlewareFor('destroy', $can(SystemPermission::ScopesDelete));
        Route::controller(ScopeBindingController::class)->prefix('scopes/{id}')->group(function () use ($can): void {
            Route::put('services/{serviceId}', 'bindService')->middleware($can(SystemPermission::ScopesBind));
            Route::delete('services/{serviceId}', 'unbindService')->middleware($can(SystemPermission::ScopesUnbind));
            Route::put('routes/{routeId}', 'bindRoute')->middleware($can(SystemPermission::ScopesBind));
            Route::delete('routes/{routeId}', 'unbindRoute')->middleware($can(SystemPermission::ScopesUnbind));
        });

        Route::apiResource('clients', ClientController::class)->only(['index', 'show', 'store', 'destroy'])->parameters(['clients' => 'id'])
            ->middlewareFor(['index', 'show'], $can(SystemPermission::ClientsView))
            ->middlewareFor('store', $can(SystemPermission::ClientsCreate))
            ->middlewareFor('destroy', $can(SystemPermission::ClientsRevoke));
        Route::put('clients/{id}/scopes', [ClientController::class, 'syncScopes'])
            ->middleware($can(SystemPermission::ClientsAssignScopes));
    });
