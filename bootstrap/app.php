<?php

declare(strict_types=1);

use App\Exceptions\ApiException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: static function (): void {
            Route::group([], __DIR__ . '/../routes/gateway.php');
        },
    )
    // Listeners are listed explicitly in App\Providers\EventServiceProvider.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['permission' => PermissionMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn(Request $request): bool => $request->is('api/*', 'gateway/*', 'oauth/*') || $request->expectsJson(),
        );

        // Expected business outcomes are answers to the caller, not application failures.
        $exceptions->dontReport(ApiException::class);

        $exceptions->render(
            static fn(ApiException $e) => response()->json(['message' => $e->getMessage()], $e->httpStatus()),
        );
    })->create();
