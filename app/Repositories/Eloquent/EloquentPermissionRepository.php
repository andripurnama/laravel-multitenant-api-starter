<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;

class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function getAll(): Collection
    {
        return Permission::all();
    }

    public function assignToRole(int $roleId, int $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        $role->givePermissionTo($permission);
    }
}
