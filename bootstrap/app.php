<?php

use App\Exceptions\Auth\AuthException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
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
