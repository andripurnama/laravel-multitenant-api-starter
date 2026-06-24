<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

interface PermissionServiceInterface
{
    /**
     * Assign role to user within tenant context
     *
     * @param  User  $user  User to assign role to
     * @param  string  $roleName  Name of the role
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if role was assigned successfully
     */
    public function assignRole(User $user, string $roleName, int $tenantId): bool;

    /**
     * Remove role from user
     *
     * @param  User  $user  User to remove role from
     * @param  string  $roleName  Name of the role
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if role was removed successfully
     */
    public function removeRole(User $user, string $roleName, int $tenantId): bool;

    /**
     * Check if user has permission within tenant
     *
     * @param  User  $user  User to check
     * @param  string  $permission  Permission name
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if user has the permission
     */
    public function hasPermission(User $user, string $permission, int $tenantId): bool;

    /**
     * Check if user has role within tenant
     *
     * @param  User  $user  User to check
     * @param  string  $role  Role name
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if user has the role
     */
    public function hasRole(User $user, string $role, int $tenantId): bool;

    /**
     * Get all permissions for user within tenant
     *
     * @param  User  $user  User to get permissions for
     * @param  int  $tenantId  Tenant identifier
     * @return Collection Collection of permissions
     */
    public function getUserPermissions(User $user, int $tenantId): Collection;

    /**
     * Create a new role within tenant
     *
     * @param  string  $name  Name of the role
     * @param  int  $tenantId  Tenant identifier
     * @param  string|null  $guardName  Guard name (defaults to 'api')
     * @return Role The created role
     */
    public function createRole(string $name, int $tenantId, ?string $guardName = null): Role;

    /**
     * Assign permission to role
     *
     * @param  string  $roleName  Name of the role
     * @param  string  $permissionName  Name of the permission
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if permission was assigned successfully
     */
    public function assignPermissionToRole(string $roleName, string $permissionName, int $tenantId): bool;

    /**
     * Sync permissions for a role
     *
     * @param  string  $roleName  Name of the role
     * @param  array  $permissions  Array of permission names
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if permissions were synced successfully
     */
    public function syncRolePermissions(string $roleName, array $permissions, int $tenantId): bool;
}
