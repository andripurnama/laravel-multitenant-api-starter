<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifiedEmailMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email not verified'], 403);
        }

        return $next($request);
    }
}
