<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function find(int $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function create(array $data): Tenant;

    public function update(Tenant $tenant, array $data): Tenant;

    public function delete(Tenant $tenant): bool;
}
