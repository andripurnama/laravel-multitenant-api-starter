<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Eloquent\EloquentRoleRepository;

test('creates role within tenant', function () {
    $tenant = Tenant::factory()->create();
    $repository = new EloquentRoleRepository();

    $role = $repository->create([
        'name' => 'admin',
        'guard_name' => 'web',
        'tenant_id' => $tenant->id,
    ]);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->tenant_id)->toBe($tenant->id)
        ->and($role->name)->toBe('admin');

    $this->assertDatabaseHas('roles', [
        'name' => 'admin',
        'tenant_id' => $tenant->id,
    ]);
});

test('findByName scopes to tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    Role::create(['name' => 'admin', 'guard_name' => 'web', 'tenant_id' => $tenant1->id]);
    Role::create(['name' => 'admin', 'guard_name' => 'web', 'tenant_id' => $tenant2->id]);

    $repository = new EloquentRoleRepository();
    $role = $repository->findByName('admin', $tenant1->id);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->tenant_id)->toBe($tenant1->id);
});

test('assignToUser within same tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $role = Role::create(['name' => 'admin', 'guard_name' => 'web', 'tenant_id' => $tenant->id]);

    $repository = new EloquentRoleRepository();
    $repository->assignToUser($user, $role);

    // Verify the role was attached in the database
    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => $role->id,
        'model_id' => $user->id,
        'model_type' => User::class,
        'tenant_id' => $tenant->id,
    ]);
});
