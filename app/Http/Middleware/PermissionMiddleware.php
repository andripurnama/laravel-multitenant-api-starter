<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permissions, string $logic = 'or')
    {
        $user = $request->user();
        $permissionArray = explode('|', $permissions);

        $hasAccess = $logic === 'and'
            ? $this->hasAllPermissions($user, $permissionArray)
            : $this->hasAnyPermission($user, $permissionArray);

        if (!$hasAccess) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        return $next($request);
    }

    private function hasAnyPermission($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }
        return false;
    }

    private function hasAllPermissions($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$user->hasPermissionTo($permission)) {
                return false;
            }
        }
        return true;
    }
}
