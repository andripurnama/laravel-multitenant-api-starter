<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register via api', function () {
    $tenant = Tenant::factory()->create();

    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'tenant_id' => $tenant->id,
    ], [
        'X-Tenant-ID' => $tenant->id,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'name', 'email', 'tenant_id'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'tenant_id' => $tenant->id,
    ]);
});

test('authenticated user can view profile', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/auth/profile', [
            'X-Tenant-ID' => $tenant->id,
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'name', 'email', 'roles'],
        ])
        ->assertJsonMissing(['password']);
});
