<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findByName(string $name, int $tenantId): ?Role
    {
        return Role::where('name', $name)->where('tenant_id', $tenantId)->first();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function getAllByTenant(int $tenantId): Collection
    {
        return Role::where('tenant_id', $tenantId)->get();
    }

    public function assignToUser(User $user, Role $role): void
    {
        // Set the team (tenant) context for Spatie Permission
        // Spatie Permission uses teams feature for multi-tenancy
        setPermissionsTeamId($user->tenant_id);

        // Use Spatie's assignRole method which handles the pivot table correctly
        $user->assignRole($role);
    }

    public function removeFromUser(User $user, Role $role): void
    {
        $user->removeRole($role);
    }

    public function syncPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);
    }
}
