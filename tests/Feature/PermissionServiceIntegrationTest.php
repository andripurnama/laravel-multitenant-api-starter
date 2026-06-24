<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Contracts\PermissionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PermissionServiceInterface::class);
});

test('can assign and check role for user in tenant', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $role = Role::create([
        'name' => 'admin',
        'guard_name' => 'api',
        'tenant_id' => $tenant->id,
    ]);

    // Act
    $result = $this->service->assignRole($user, 'admin', $tenant->id);

    // Assert
    expect($result)->toBeTrue();
    expect($this->service->hasRole($user, 'admin', $tenant->id))->toBeTrue();
});

test('can remove role from user', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $role = Role::create([
        'name' => 'editor',
        'guard_name' => 'api',
        'tenant_id' => $tenant->id,
    ]);
    
    $this->service->assignRole($user, 'editor', $tenant->id);

    // Act
    $result = $this->service->removeRole($user, 'editor', $tenant->id);

    // Assert
    expect($result)->toBeTrue();
    expect($this->service->hasRole($user, 'editor', $tenant->id))->toBeFalse();
});

test('can check permissions through roles', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    $permission = Permission::create(['name' => 'edit-posts', 'guard_name' => 'api']);
    
    $role = Role::create([
        'name' => 'editor',
        'guard_name' => 'api',
        'tenant_id' => $tenant->id,
    ]);
    
    $role->givePermissionTo($permission);
    $this->service->assignRole($user, 'editor', $tenant->id);

    // Act
    $hasPermission = $this->service->hasPermission($user, 'edit-posts', $tenant->id);

    // Assert
    expect($hasPermission)->toBeTrue();
});

test('can get all user permissions within tenant', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    $permission1 = Permission::create(['name' => 'edit-posts', 'guard_name' => 'api']);
    $permission2 = Permission::create(['name' => 'delete-posts', 'guard_name' => 'api']);
    
    $role = Role::create([
        'name' => 'editor',
        'guard_name' => 'api',
        'tenant_id' => $tenant->id,
    ]);
    
    $role->givePermissionTo([$permission1, $permission2]);
    $this->service->assignRole($user, 'editor', $tenant->id);

    // Act
    $permissions = $this->service->getUserPermissions($user, $tenant->id);

    // Assert
    expect($permissions)->toHaveCount(2)
        ->and($permissions->pluck('name')->toArray())->toContain('edit-posts', 'delete-posts');
});

test('tenant isolation prevents cross-tenant role assignment', function () {
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant1->id]);
    
    Role::create([
        'name' => 'admin',
        'guard_name' => 'api',
        'tenant_id' => $tenant2->id,
    ]);

    // Act & Assert
    expect(fn () => $this->service->assignRole($user, 'admin', $tenant2->id))
        ->toThrow(\App\Exceptions\Auth\CrossTenantAccessException::class);
});
