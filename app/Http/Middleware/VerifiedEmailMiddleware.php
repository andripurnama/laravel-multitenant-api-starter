<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class VerifiedEmailMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return ApiResponse::error('Email not verified', 403);
        }

        return $next($request);
    }
}
