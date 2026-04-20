<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant can be created', function () {
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    expect($tenant)->toBeInstanceOf(Tenant::class)
        ->and($tenant->name)->toBe('Test Tenant')
        ->and($tenant->slug)->toBe('test-tenant');
});

test('tenant can be made current', function () {
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    $tenant->makeCurrent();

    expect(Tenant::current())->not->toBeNull()
        ->and(Tenant::current()->id)->toBe($tenant->id);
});

test('tenant can be forgotten', function () {
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    $tenant->makeCurrent();
    expect(Tenant::current())->not->toBeNull();

    Tenant::forgetCurrent();
    expect(Tenant::current())->toBeNull();
});

test('tenant has users relationship', function () {
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    expect($tenant->users())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('tenant has roles relationship', function () {
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    expect($tenant->roles())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
