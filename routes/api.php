<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Middleware\TenantContextMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware([TenantContextMiddleware::class])->group(function () {
    // Public auth routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/password/reset-request', [AuthController::class, 'requestPasswordReset']);
    Route::post('/auth/password/reset', [AuthController::class, 'resetPassword']);
    Route::get('/auth/email/verify/{token}', [AuthController::class, 'verifyEmail']);

    // Protected auth routes
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/profile', [AuthController::class, 'profile']);
        Route::post('/auth/email/verify-send', [AuthController::class, 'sendVerification']);

        // Permission management routes
        Route::post('/permissions/assign-role', [PermissionController::class, 'assignRole']);
        Route::post('/permissions/remove-role', [PermissionController::class, 'removeRole']);
        Route::post('/permissions/assign-permission', [PermissionController::class, 'assignPermission']);
        
        // Role management routes
        Route::post('/roles', [PermissionController::class, 'createRole']);
        Route::post('/roles/{role}/permissions', [PermissionController::class, 'syncRolePermissions']);
    });
});
