<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles, string $logic = 'or')
    {
        $user = $request->user();
        $roleArray = explode('|', $roles);

        $hasAccess = $logic === 'and'
            ? $this->hasAllRoles($user, $roleArray)
            : $this->hasAnyRole($user, $roleArray);

        if (! $hasAccess) {
            return ApiResponse::error('Insufficient permissions', 403);
        }

        return $next($request);
    }

    private function hasAnyRole($user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function hasAllRoles($user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (! $user->hasRole($role)) {
                return false;
            }
        }

        return true;
    }
}
