<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function findByName(string $name, int $tenantId): ?Role;
    public function create(array $data): Role;
    public function getAllByTenant(int $tenantId): Collection;
    public function assignToUser(User $user, Role $role): void;
    public function removeFromUser(User $user, Role $role): void;
    public function syncPermissions(Role $role, array $permissions): void;
}
