<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\TenantContext::class);

        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentUserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\RoleRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentRoleRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\TokenRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentTokenRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\PermissionRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentPermissionRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\TenantRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentTenantRepository::class
        );

        $this->app->bind(
            \App\Services\Contracts\AuthServiceInterface::class,
            \App\Services\AuthService::class
        );

        $this->app->bind(
            \App\Services\Contracts\PermissionServiceInterface::class,
            \App\Services\PermissionService::class
        );

        $this->app->bind(
            \App\Services\Contracts\TokenServiceInterface::class,
            \App\Services\TokenService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sanctum token expiration is configured in config/sanctum.php
        // or via SANCTUM_EXPIRATION environment variable
    }
}
