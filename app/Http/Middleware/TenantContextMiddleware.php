<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Auth\CrossTenantAccessException;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;

class TenantContextMiddleware
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant ID required'], 400);
        }

        $tenant = $this->tenantRepository->find((int) $tenantId);

        if (!$tenant) {
            throw new CrossTenantAccessException("Tenant {$tenantId} not found");
        }

        $this->tenantContext->setTenant($tenant->id);

        return $next($request);
    }
}
