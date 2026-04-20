<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users are scoped to current tenant', function () {
    $tenant1 = Tenant::create(['name' => 'Tenant 1', 'slug' => 'tenant-1']);
    $tenant2 = Tenant::create(['name' => 'Tenant 2', 'slug' => 'tenant-2']);

    $tenant1->makeCurrent();
    $user1 = User::create([
        'name' => 'User 1',
        'email' => 'user1@tenant1.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenant1->id,
    ]);

    $tenant2->makeCurrent();
    $user2 = User::create([
        'name' => 'User 2',
        'email' => 'user2@tenant2.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenant2->id,
    ]);

    // When tenant 1 is current, only see tenant 1 users
    $tenant1->makeCurrent();
    expect(User::count())->toBe(1)
        ->and(User::first()->id)->toBe($user1->id);

    // When tenant 2 is current, only see tenant 2 users
    $tenant2->makeCurrent();
    expect(User::count())->toBe(1)
        ->and(User::first()->id)->toBe($user2->id);
});

test('models automatically get tenant_id when created', function () {
    $tenant = Tenant::create(['name' => 'Test Tenant', 'slug' => 'test-tenant']);
    $tenant->makeCurrent();

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    expect($user->tenant_id)->toBe($tenant->id);
});

test('switching tenants changes query scope', function () {
    $tenant1 = Tenant::create(['name' => 'Tenant 1', 'slug' => 'tenant-1']);
    $tenant2 = Tenant::create(['name' => 'Tenant 2', 'slug' => 'tenant-2']);

    $tenant1->makeCurrent();
    User::create([
        'name' => 'User 1',
        'email' => 'user1@tenant1.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenant1->id,
    ]);
    User::create([
        'name' => 'User 2',
        'email' => 'user2@tenant1.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenant1->id,
    ]);

    $tenant2->makeCurrent();
    User::create([
        'name' => 'User 3',
        'email' => 'user3@tenant2.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenant2->id,
    ]);

    $tenant1->makeCurrent();
    expect(User::count())->toBe(2);

    $tenant2->makeCurrent();
    expect(User::count())->toBe(1);
});
