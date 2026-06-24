<?php

declare(strict_types=1);

use App\Exceptions\Auth\CrossTenantAccessException;
use App\Exceptions\Auth\PermissionNotFoundException;
use App\Exceptions\Auth\RoleNotFoundException;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PermissionService;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->roleRepository = Mockery::mock(RoleRepositoryInterface::class);
    $this->permissionRepository = Mockery::mock(PermissionRepositoryInterface::class);
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);

    $this->service = new PermissionService(
        $this->roleRepository,
        $this->permissionRepository,
        $this->userRepository
    );
});

afterEach(function () {
    Mockery::close();
});

describe('assignRole', function () {
    test('assigns role to user within same tenant', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);
        $role = new Role(['id' => 1, 'name' => 'admin', 'tenant_id' => 1]);

        $this->roleRepository->shouldReceive('findByName')->with('admin', 1)->andReturn($role);
        $this->roleRepository->shouldReceive('assignToUser')->with($user, $role);

        expect($this->service->assignRole($user, 'admin', 1))->toBeTrue();
    });

    test('throws exception when role not found', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);
        $this->roleRepository->shouldReceive('findByName')->with('nonexistent', 1)->andReturn(null);

        $this->service->assignRole($user, 'nonexistent', 1);
    })->throws(RoleNotFoundException::class);

    test('throws exception when user and tenant mismatch', function () {
        $this->service->assignRole(new User(['id' => 1, 'tenant_id' => 1]), 'admin', 2);
    })->throws(CrossTenantAccessException::class);

    test('assigns multiple roles to user within same tenant', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);
        $adminRole = new Role(['id' => 1, 'name' => 'admin', 'tenant_id' => 1]);
        $editorRole = new Role(['id' => 2, 'name' => 'editor', 'tenant_id' => 1]);

        $this->roleRepository->shouldReceive('findByName')->with('admin', 1)->andReturn($adminRole);
        $this->roleRepository->shouldReceive('assignToUser')->with($user, $adminRole);
        $this->roleRepository->shouldReceive('findByName')->with('editor', 1)->andReturn($editorRole);
        $this->roleRepository->shouldReceive('assignToUser')->with($user, $editorRole);

        expect($this->service->assignRole($user, 'admin', 1))->toBeTrue()
            ->and($this->service->assignRole($user, 'editor', 1))->toBeTrue();
    });

    test('prevents role assignment across different tenants', function () {
        $this->service->assignRole(new User(['id' => 1, 'tenant_id' => 1]), 'admin', 2);
    })->throws(CrossTenantAccessException::class, 'User does not belong to tenant 2');
});

describe('removeRole', function () {
    test('removes role from user within same tenant', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);
        $role = new Role(['id' => 1, 'name' => 'admin', 'tenant_id' => 1]);

        $this->roleRepository->shouldReceive('findByName')->with('admin', 1)->andReturn($role);
        $this->roleRepository->shouldReceive('removeFromUser')->with($user, $role);

        expect($this->service->removeRole($user, 'admin', 1))->toBeTrue();
    });

    test('throws exception when role not found', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);
        $this->roleRepository->shouldReceive('findByName')->with('nonexistent', 1)->andReturn(null);

        $this->service->removeRole($user, 'nonexistent', 1);
    })->throws(RoleNotFoundException::class);

    test('throws exception when user and tenant mismatch', function () {
        $this->service->removeRole(new User(['id' => 1, 'tenant_id' => 1]), 'admin', 2);
    })->throws(CrossTenantAccessException::class);
});

describe('hasPermission', function () {
    test('returns true when user has permission through role', function () {
        $user = Mockery::mock(User::class);
        $user->allows(['getAttribute' => 1, 'relationLoaded' => true]);
        $user->shouldReceive('hasPermissionTo')->with('edit-posts', 'api')->andReturn(true);

        expect($this->service->hasPermission($user, 'edit-posts', 1))->toBeTrue();
    });

    test('returns false when user does not have permission', function () {
        $user = Mockery::mock(User::class);
        $user->allows(['getAttribute' => 1, 'relationLoaded' => true]);
        $user->shouldReceive('hasPermissionTo')->with('delete-posts', 'api')->andReturn(false);

        expect($this->service->hasPermission($user, 'delete-posts', 1))->toBeFalse();
    });

    test('throws exception when user and tenant mismatch', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);

        $this->service->hasPermission($user, 'edit-posts', 2);
    })->throws(CrossTenantAccessException::class);

    test('checks multiple permissions for user', function () {
        $user = Mockery::mock(User::class);
        $user->allows(['getAttribute' => 1, 'relationLoaded' => true]);
        $user->shouldReceive('hasPermissionTo')->with('edit-posts', 'api')->andReturn(true);
        $user->shouldReceive('hasPermissionTo')->with('delete-posts', 'api')->andReturn(false);

        expect($this->service->hasPermission($user, 'edit-posts', 1))->toBeTrue()
            ->and($this->service->hasPermission($user, 'delete-posts', 1))->toBeFalse();
    });

    test('permission checking is scoped to tenant', function () {
        $user = Mockery::mock(User::class);
        $user->allows(['getAttribute' => 1, 'relationLoaded' => true]);
        $user->shouldReceive('hasPermissionTo')->with('edit-posts', 'api')->andReturn(true);

        expect($this->service->hasPermission($user, 'edit-posts', 1))->toBeTrue();

        expect(fn () => $this->service->hasPermission(
            new User(['id' => 2, 'tenant_id' => 2]), 
            'edit-posts', 
            1
        ))->toThrow(CrossTenantAccessException::class);
    });

    test('loads roles relationship if not already loaded', function () {
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('tenant_id')->andReturn(1);
        $user->shouldReceive('relationLoaded')->with('roles')->andReturn(false);
        $user->shouldReceive('load')->once()->with('roles.permissions')->andReturnSelf();
        $user->shouldReceive('hasPermissionTo')->with('edit-posts', 'api')->andReturn(true);

        expect($this->service->hasPermission($user, 'edit-posts', 1))->toBeTrue();
    });
});

describe('hasRole', function () {
    test('returns true when user has role in tenant', function () {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('tenant_id')->andReturn(1);

        $role = new Role(['id' => 1, 'name' => 'admin', 'tenant_id' => 1]);

        $this->roleRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('admin', 1)
            ->andReturn($role);

        $roles = collect([$role]);
        $user->shouldReceive('load')->with('roles')->andReturnSelf();
        $user->shouldReceive('getAttribute')->with('roles')->andReturn($roles);

        $result = $this->service->hasRole($user, 'admin', 1);

        expect($result)->toBeTrue();
    });

    test('returns false when user does not have role', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);

        $this->roleRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('admin', 1)
            ->andReturn(null);

        $result = $this->service->hasRole($user, 'admin', 1);

        expect($result)->toBeFalse();
    });

    test('throws exception when user and tenant mismatch', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);

        $this->service->hasRole($user, 'admin', 2);
    })->throws(CrossTenantAccessException::class);
});

describe('getUserPermissions', function () {
    test('returns all permissions for user within tenant', function () {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('tenant_id')->andReturn(1);

        $permission1 = new Permission(['id' => 1, 'name' => 'edit-posts']);
        $permission2 = new Permission(['id' => 2, 'name' => 'delete-posts']);
        $permissions = collect([$permission1, $permission2]);

        $user->shouldReceive('getPermissionsViaRoles')
            ->once()
            ->andReturn($permissions);

        $result = $this->service->getUserPermissions($user, 1);

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result->count())->toBe(2)
            ->and($result->first()->name)->toBe('edit-posts');
    });

    test('throws exception when user and tenant mismatch', function () {
        $user = new User(['id' => 1, 'tenant_id' => 1]);

        $this->service->getUserPermissions($user, 2);
    })->throws(CrossTenantAccessException::class);
});

describe('createRole', function () {
    test('creates role with tenant context', function () {
        $roleData = ['name' => 'editor', 'tenant_id' => 1, 'guard_name' => 'api'];
        $role = new Role($roleData);

        $this->roleRepository->shouldReceive('create')->with($roleData)->andReturn($role);

        $result = $this->service->createRole('editor', 1, 'api');

        expect($result)->toBeInstanceOf(Role::class)
            ->and($result->name)->toBe('editor')
            ->and($result->tenant_id)->toBe(1);
    });

    test('creates role with default guard name', function () {
        $this->roleRepository->shouldReceive('create')
            ->with(['name' => 'editor', 'tenant_id' => 1, 'guard_name' => 'api'])
            ->andReturn(new Role());

        expect($this->service->createRole('editor', 1))->toBeInstanceOf(Role::class);
    });
});

describe('assignPermissionToRole', function () {
    test('assigns permission to role within tenant', function () {
        $role = new Role(['id' => 1, 'name' => 'editor', 'tenant_id' => 1]);
        $role->id = 1;
        $permission = new Permission(['id' => 1, 'name' => 'edit-posts']);
        $permission->id = 1;

        $this->roleRepository->shouldReceive('findByName')->with('editor', 1)->andReturn($role);
        $this->permissionRepository->shouldReceive('findByName')->with('edit-posts')->andReturn($permission);
        $this->permissionRepository->shouldReceive('assignToRole')->with($role->id, $permission->id);

        expect($this->service->assignPermissionToRole('editor', 'edit-posts', 1))->toBeTrue();
    });

    test('throws exception when role not found', function () {
        $this->roleRepository->shouldReceive('findByName')->with('nonexistent', 1)->andReturn(null);
        $this->service->assignPermissionToRole('nonexistent', 'edit-posts', 1);
    })->throws(RoleNotFoundException::class);

    test('throws exception when permission not found', function () {
        $this->roleRepository->shouldReceive('findByName')->with('editor', 1)->andReturn(new Role(['id' => 1]));
        $this->permissionRepository->shouldReceive('findByName')->with('nonexistent')->andReturn(null);
        $this->service->assignPermissionToRole('editor', 'nonexistent', 1);
    })->throws(PermissionNotFoundException::class);
});

describe('syncRolePermissions', function () {
    test('syncs permissions to role within tenant', function () {
        $role = new Role(['id' => 1, 'name' => 'editor', 'tenant_id' => 1]);
        $permission1 = new Permission(['id' => 1, 'name' => 'edit-posts']);
        $permission2 = new Permission(['id' => 2, 'name' => 'delete-posts']);

        $this->roleRepository->shouldReceive('findByName')->with('editor', 1)->andReturn($role);
        $this->permissionRepository->shouldReceive('findByName')->with('edit-posts')->andReturn($permission1);
        $this->permissionRepository->shouldReceive('findByName')->with('delete-posts')->andReturn($permission2);
        $this->roleRepository->shouldReceive('syncPermissions')->with($role, [$permission1, $permission2]);

        expect($this->service->syncRolePermissions('editor', ['edit-posts', 'delete-posts'], 1))->toBeTrue();
    });

    test('throws exception when role not found', function () {
        $this->roleRepository->shouldReceive('findByName')->with('nonexistent', 1)->andReturn(null);
        $this->service->syncRolePermissions('nonexistent', ['edit-posts'], 1);
    })->throws(RoleNotFoundException::class);

    test('throws exception when any permission not found', function () {
        $role = new Role(['id' => 1, 'name' => 'editor', 'tenant_id' => 1]);
        $permission1 = new Permission(['id' => 1, 'name' => 'edit-posts']);

        $this->roleRepository->shouldReceive('findByName')->with('editor', 1)->andReturn($role);
        $this->permissionRepository->shouldReceive('findByName')->with('edit-posts')->andReturn($permission1);
        $this->permissionRepository->shouldReceive('findByName')->with('nonexistent')->andReturn(null);

        $this->service->syncRolePermissions('editor', ['edit-posts', 'nonexistent'], 1);
    })->throws(PermissionNotFoundException::class);
});
