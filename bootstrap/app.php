<?php

use App\Exceptions\Auth\AuthException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantContextMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'verified' => \App\Http\Middleware\VerifiedEmailMiddleware::class,
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
    })->create();
