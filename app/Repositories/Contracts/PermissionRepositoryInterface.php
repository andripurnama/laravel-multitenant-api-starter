<?php

namespace App\Repositories\Contracts;

use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;

interface PermissionRepositoryInterface
{
    public function findByName(string $name): ?Permission;
    public function create(array $data): Permission;
    public function getAll(): Collection;
    public function assignToRole(int $roleId, int $permissionId): void;
}
