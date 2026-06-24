<?php

use App\Exceptions\Auth\AuthException;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantContextMiddleware;
use App\Http\Middleware\VerifiedEmailMiddleware;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => TenantContextMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'verified' => VerifiedEmailMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Register custom exception rendering for authentication and authorization exceptions
        $exceptions->render(function (AuthException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(
                    $e->getResponseData(),
                    $e->getStatusCode()
                );
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    $e->getMessage(),
                    422,
                    $e->errors()
                );
            }
        });
    })->create();
