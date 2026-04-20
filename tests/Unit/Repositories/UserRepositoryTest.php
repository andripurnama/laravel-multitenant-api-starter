<?php

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Eloquent\EloquentUserRepository;

uses()->group('repository');

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

test('creates user within tenant', function () {
    $tenant = Tenant::factory()->create();
    $repository = new EloquentUserRepository();

    $user = $repository->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenant->id,
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->tenant_id)->toBe($tenant->id)
        ->and($user->email)->toBe('john@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'tenant_id' => $tenant->id,
    ]);
});

test('findByEmail scopes to tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    User::factory()->create([
        'email' => 'user@example.com',
        'tenant_id' => $tenant1->id,
    ]);

    User::factory()->create([
        'email' => 'user@example.com',
        'tenant_id' => $tenant2->id,
    ]);

    $repository = new EloquentUserRepository();
    $user = $repository->findByEmail('user@example.com');

    expect($user)->toBeInstanceOf(User::class);
});

test('getAllByTenant returns only tenant users', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    User::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
    User::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

    $repository = new EloquentUserRepository();
    $users = $repository->getAllByTenant($tenant1->id);

    expect($users)->toHaveCount(3)
        ->and($users->every(fn($user) => $user->tenant_id === $tenant1->id))->toBeTrue();
});
