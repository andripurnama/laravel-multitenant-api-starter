<?php

namespace App\Providers;

use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentPermissionRepository;
use App\Repositories\Eloquent\EloquentRoleRepository;
use App\Repositories\Eloquent\EloquentTenantRepository;
use App\Repositories\Eloquent\EloquentTokenRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\AuthService;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\PermissionServiceInterface;
use App\Services\Contracts\TokenServiceInterface;
use App\Services\PermissionService;
use App\Services\TenantContext;
use App\Services\TokenService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);

        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            RoleRepositoryInterface::class,
            EloquentRoleRepository::class
        );

        $this->app->bind(
            TokenRepositoryInterface::class,
            EloquentTokenRepository::class
        );

        $this->app->bind(
            PermissionRepositoryInterface::class,
            EloquentPermissionRepository::class
        );

        $this->app->bind(
            TenantRepositoryInterface::class,
            EloquentTenantRepository::class
        );

        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );

        $this->app->bind(
            PermissionServiceInterface::class,
            PermissionService::class
        );

        $this->app->bind(
            TokenServiceInterface::class,
            TokenService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // Sanctum token expiration is configured in config/sanctum.php
        // or via SANCTUM_EXPIRATION environment variable
    }
}
