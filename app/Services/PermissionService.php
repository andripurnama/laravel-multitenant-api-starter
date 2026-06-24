<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Auth\CrossTenantAccessException;
use App\Exceptions\Auth\PermissionNotFoundException;
use App\Exceptions\Auth\RoleNotFoundException;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\PermissionServiceInterface;
use Illuminate\Support\Collection;

class PermissionService implements PermissionServiceInterface
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Assign role to user within tenant context
     *
     * @param  User  $user  User to assign role to
     * @param  string  $roleName  Name of the role
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if role was assigned successfully
     *
     * @throws CrossTenantAccessException If user does not belong to the specified tenant
     * @throws RoleNotFoundException If role does not exist in the tenant
     */
    public function assignRole(User $user, string $roleName, int $tenantId): bool
    {
        // Validate tenant context (Requirement 6.1, 6.2)
        $this->validateTenantContext($user, $tenantId);

        // Find role within tenant context (Requirement 6.2)
        $role = $this->roleRepository->findByName($roleName, $tenantId);

        if (! $role) {
            throw new RoleNotFoundException("Role '{$roleName}' not found in tenant {$tenantId}");
        }

        // Assign role to user (Requirement 6.1)
        $this->roleRepository->assignToUser($user, $role);

        return true;
    }

    /**
     * Remove role from user
     *
     * @param  User  $user  User to remove role from
     * @param  string  $roleName  Name of the role
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if role was removed successfully
     *
     * @throws CrossTenantAccessException If user does not belong to the specified tenant
     * @throws RoleNotFoundException If role does not exist in the tenant
     */
    public function removeRole(User $user, string $roleName, int $tenantId): bool
    {
        // Validate tenant context
        $this->validateTenantContext($user, $tenantId);

        // Find role within tenant context
        $role = $this->roleRepository->findByName($roleName, $tenantId);

        if (! $role) {
            throw new RoleNotFoundException("Role '{$roleName}' not found in tenant {$tenantId}");
        }

        // Remove role from user
        $this->roleRepository->removeFromUser($user, $role);

        return true;
    }

    /**
     * Check if user has permission within tenant
     *
     * @param  User  $user  User to check
     * @param  string  $permission  Permission name
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if user has the permission
     *
     * @throws CrossTenantAccessException If user does not belong to the specified tenant
     */
    public function hasPermission(User $user, string $permission, int $tenantId): bool
    {
        // Validate tenant context (Requirement 8.1, 11.5)
        $this->validateTenantContext($user, $tenantId);

        // Refresh user's permissions if not loaded
        if (! $user->relationLoaded('roles')) {
            $user->load('roles.permissions');
        }

        // Check permission through roles (Requirement 8.2)
        // Spatie's hasPermissionTo method checks permissions through assigned roles
        return $user->hasPermissionTo($permission, 'api');
    }

    /**
     * Check if user has role within tenant
     *
     * @param  User  $user  User to check
     * @param  string  $role  Role name
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if user has the role
     *
     * @throws CrossTenantAccessException If user does not belong to the specified tenant
     */
    public function hasRole(User $user, string $role, int $tenantId): bool
    {
        // Validate tenant context (Requirement 8.1, 11.5)
        $this->validateTenantContext($user, $tenantId);

        // Find role within tenant context
        $roleModel = $this->roleRepository->findByName($role, $tenantId);

        if (! $roleModel) {
            return false;
        }

        // Refresh roles if not loaded
        $user->load('roles');

        // Check if user has the role
        return $user->roles->contains('id', $roleModel->id);
    }

    /**
     * Get all permissions for user within tenant
     *
     * @param  User  $user  User to get permissions for
     * @param  int  $tenantId  Tenant identifier
     * @return Collection Collection of permissions
     *
     * @throws CrossTenantAccessException If user does not belong to the specified tenant
     */
    public function getUserPermissions(User $user, int $tenantId): Collection
    {
        // Validate tenant context (Requirement 11.5)
        $this->validateTenantContext($user, $tenantId);

        // Get permissions via roles scoped to tenant
        // The User model's getPermissionsViaRoles method already filters by tenant_id
        return $user->getPermissionsViaRoles();
    }

    /**
     * Create a new role within tenant
     *
     * @param  string  $name  Name of the role
     * @param  int  $tenantId  Tenant identifier
     * @param  string|null  $guardName  Guard name (defaults to 'api')
     * @return Role The created role
     */
    public function createRole(string $name, int $tenantId, ?string $guardName = null): Role
    {
        // Default guard name to 'api' if not provided (Requirement 7.1)
        $guardName = $guardName ?? 'api';

        // Create role with tenant context (Requirement 7.1, 11.1)
        return $this->roleRepository->create([
            'name' => $name,
            'tenant_id' => $tenantId,
            'guard_name' => $guardName,
        ]);
    }

    /**
     * Assign permission to role
     *
     * @param  string  $roleName  Name of the role
     * @param  string  $permissionName  Name of the permission
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if permission was assigned successfully
     *
     * @throws RoleNotFoundException If role does not exist in the tenant
     * @throws PermissionNotFoundException If permission does not exist
     */
    public function assignPermissionToRole(string $roleName, string $permissionName, int $tenantId): bool
    {
        // Find role within tenant context (Requirement 7.2)
        $role = $this->roleRepository->findByName($roleName, $tenantId);

        if (! $role) {
            throw new RoleNotFoundException("Role '{$roleName}' not found in tenant {$tenantId}");
        }

        // Find permission (Requirement 7.2)
        $permission = $this->permissionRepository->findByName($permissionName);

        if (! $permission) {
            throw new PermissionNotFoundException(
                "Permission '{$permissionName}' not found"
            );
        }

        // Assign permission to role (Requirement 7.1, 7.4)
        $this->permissionRepository->assignToRole($role->id, $permission->id);

        return true;
    }

    /**
     * Sync permissions for a role
     *
     * @param  string  $roleName  Name of the role
     * @param  array  $permissions  Array of permission names
     * @param  int  $tenantId  Tenant identifier
     * @return bool True if permissions were synced successfully
     *
     * @throws RoleNotFoundException If role does not exist in the tenant
     * @throws PermissionNotFoundException If any permission does not exist
     */
    public function syncRolePermissions(string $roleName, array $permissions, int $tenantId): bool
    {
        // Find role within tenant context (Requirement 7.4)
        $role = $this->roleRepository->findByName($roleName, $tenantId);

        if (! $role) {
            throw new RoleNotFoundException("Role '{$roleName}' not found in tenant {$tenantId}");
        }

        // Find all permissions and validate they exist (Requirement 7.4)
        $permissionModels = [];
        foreach ($permissions as $permissionName) {
            $permission = $this->permissionRepository->findByName($permissionName);

            if (! $permission) {
                throw new PermissionNotFoundException(
                    "Permission '{$permissionName}' not found"
                );
            }

            $permissionModels[] = $permission;
        }

        // Sync permissions to role (Requirement 7.4)
        $this->roleRepository->syncPermissions($role, $permissionModels);

        return true;
    }

    /**
     * Validate that user belongs to the specified tenant
     *
     * @param  User  $user  User to validate
     * @param  int  $tenantId  Expected tenant identifier
     *
     * @throws CrossTenantAccessException If user does not belong to the specified tenant
     */
    private function validateTenantContext(User $user, int $tenantId): void
    {
        if ($user->tenant_id !== $tenantId) {
            throw new CrossTenantAccessException(
                "User does not belong to tenant {$tenantId}"
            );
        }
    }
}
