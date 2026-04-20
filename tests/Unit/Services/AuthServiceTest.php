<?php

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);
uses()->group('service');

// Task 8.1: Test user registration
// **Property 2: Tenant-Scoped Email Uniqueness**
// **Property 3: Password Security**
// **Validates: Requirements 1.2, 1.3, 1.5**

test('register creates user with hashed password', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $tenant = new Tenant(['id' => 1, 'name' => 'Test Tenant', 'slug' => 'test-tenant']);
    $tenantRepo->shouldReceive('find')->with(1)->andReturn($tenant);

    $userRepo->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return isset($data['password'])
                && Hash::check('password123', $data['password'])
                && $data['tenant_id'] === 1
                && $data['email'] === 'test@example.com';
        }))
        ->andReturn(new User([
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'tenant_id' => 1,
        ]));

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);
    $user = $service->register([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ], 1);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('test@example.com');
})->group('auth-service', 'registration');

test('register duplicate email within tenant fails with database exception', function () {
    // Create tenant first
    $tenant = Tenant::factory()->create();
    
    // Create first user with email
    User::factory()->create([
        'email' => 'duplicate@example.com',
        'tenant_id' => $tenant->id,
    ]);
    
    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);
    
    $tenantRepo->shouldReceive('find')->with($tenant->id)->andReturn($tenant);
    
    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);
    
    // Attempt to create second user with same email in same tenant
    $service->register([
        'email' => 'duplicate@example.com',
        'name' => 'Duplicate User',
        'password' => 'password123',
    ], $tenant->id);
})->throws(\Illuminate\Database\QueryException::class)->group('auth-service', 'registration');

test('register duplicate email across different tenants succeeds', function () {
    // Create two tenants
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    // Create user in tenant 1
    $user1 = User::factory()->create([
        'email' => 'shared@example.com',
        'tenant_id' => $tenant1->id,
    ]);
    
    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);
    
    $tenantRepo->shouldReceive('find')->with($tenant2->id)->andReturn($tenant2);
    
    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);
    
    // Create user with same email in tenant 2 - should succeed
    $user2 = $service->register([
        'email' => 'shared@example.com',
        'name' => 'User in Tenant 2',
        'password' => 'password123',
    ], $tenant2->id);
    
    expect($user2)->toBeInstanceOf(User::class)
        ->and($user2->email)->toBe('shared@example.com')
        ->and($user2->tenant_id)->toBe($tenant2->id)
        ->and($user2->id)->not->toBe($user1->id);
    
    // Verify both users exist in database
    $this->assertDatabaseHas('users', [
        'email' => 'shared@example.com',
        'tenant_id' => $tenant1->id,
    ]);
    
    $this->assertDatabaseHas('users', [
        'email' => 'shared@example.com',
        'tenant_id' => $tenant2->id,
    ]);
})->group('auth-service', 'registration');

test('register never stores plain text password', function () {
    $tenant = Tenant::factory()->create();
    
    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);
    
    $tenantRepo->shouldReceive('find')->with($tenant->id)->andReturn($tenant);
    
    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);
    
    $plainPassword = 'mySecretPassword123!';
    
    $user = $service->register([
        'email' => 'secure@example.com',
        'name' => 'Secure User',
        'password' => $plainPassword,
    ], $tenant->id);
    
    // Verify password is hashed
    expect($user->password)->not->toBe($plainPassword)
        ->and(Hash::check($plainPassword, $user->password))->toBeTrue();
    
    // Verify plain password is not in database
    $this->assertDatabaseMissing('users', [
        'email' => 'secure@example.com',
        'password' => $plainPassword,
    ]);
})->group('auth-service', 'registration');

test('register throws exception for non-existent tenant', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $tenantRepo->shouldReceive('find')->with(999)->andReturn(null);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $service->register([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ], 999);
})->throws(InvalidArgumentException::class, 'Tenant with ID 999 not found')->group('auth-service', 'registration');

// Task 8.2: Test user login
// **Property 6: Cross-Tenant Authentication Prevention**
// **Property 7: Password Verification Correctness**
// **Property 8: Authentication Error Message Security**
// **Validates: Requirements 2.2, 2.3, 2.6, 5.3, 5.4**

test('login with valid credentials returns tokens', function () {
    // Set up Passport personal access client directly in database
    DB::table('oauth_clients')->insert([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'tenant_id' => null,
        'owner_type' => null,
        'owner_id' => null,
        'name' => 'Test Personal Access Client',
        'secret' => null,
        'provider' => 'users',
        'redirect_uris' => json_encode(['http://localhost']),
        'grant_types' => json_encode(['personal_access']),
        'revoked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create tenant and user in database
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $result = $service->login('test@example.com', 'password123', $tenant->id);

    // Verify token structure
    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['access_token', 'refresh_token', 'token_type', 'expires_in'])
        ->and($result['token_type'])->toBe('Bearer')
        ->and($result['access_token'])->not->toBeNull();
})->group('auth-service', 'login');

test('login with invalid password throws InvalidCredentialsException', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $service->login('test@example.com', 'wrongpassword', $tenant->id);
})->throws(InvalidCredentialsException::class)->group('auth-service', 'login');

test('login with wrong tenant throws InvalidCredentialsException', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant1->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Try to login with correct credentials but wrong tenant
    $service->login('test@example.com', 'password123', $tenant2->id);
})->throws(InvalidCredentialsException::class)->group('auth-service', 'login');

test('login with non-existent user throws InvalidCredentialsException', function () {
    $tenant = Tenant::factory()->create();
    
    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $service->login('nonexistent@example.com', 'password123', $tenant->id);
})->throws(InvalidCredentialsException::class)->group('auth-service', 'login');

test('login error message does not reveal which credential failed for wrong email', function () {
    $tenant = Tenant::factory()->create();
    
    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    try {
        $service->login('nonexistent@example.com', 'password123', $tenant->id);
        expect(false)->toBeTrue('Exception should have been thrown');
    } catch (InvalidCredentialsException $e) {
        // Verify error message is generic and doesn't reveal which credential failed
        $message = $e->getMessage();
        expect($message)->not->toContain('email')
            ->and($message)->not->toContain('user')
            ->and($message)->not->toContain('not found')
            ->and($message)->not->toContain('does not exist');
    }
})->group('auth-service', 'login', 'security');

test('login error message does not reveal which credential failed for wrong password', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    try {
        $service->login('test@example.com', 'wrongpassword', $tenant->id);
        expect(false)->toBeTrue('Exception should have been thrown');
    } catch (InvalidCredentialsException $e) {
        // Verify error message is generic and doesn't reveal which credential failed
        $message = $e->getMessage();
        expect($message)->not->toContain('password')
            ->and($message)->not->toContain('incorrect')
            ->and($message)->not->toContain('wrong');
    }
})->group('auth-service', 'login', 'security');

test('login error message does not reveal which credential failed for wrong tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant1->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    try {
        $service->login('test@example.com', 'password123', $tenant2->id);
        expect(false)->toBeTrue('Exception should have been thrown');
    } catch (InvalidCredentialsException $e) {
        // Verify error message is generic and doesn't reveal which credential failed
        $message = $e->getMessage();
        expect($message)->not->toContain('tenant')
            ->and($message)->not->toContain('organization')
            ->and($message)->not->toContain('cross-tenant');
    }
})->group('auth-service', 'login', 'security');

test('login with correct password succeeds', function () {
    // Set up Passport personal access client directly in database
    DB::table('oauth_clients')->insert([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'tenant_id' => null,
        'owner_type' => null,
        'owner_id' => null,
        'name' => 'Test Personal Access Client',
        'secret' => null,
        'provider' => 'users',
        'redirect_uris' => json_encode(['http://localhost']),
        'grant_types' => json_encode(['personal_access']),
        'revoked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correctPassword123!'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $result = $service->login('test@example.com', 'correctPassword123!', $tenant->id);

    expect($result)->toBeArray()
        ->and($result['access_token'])->not->toBeNull();
})->group('auth-service', 'login');

test('login with any incorrect password fails', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correctPassword123!'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Test various incorrect passwords
    $incorrectPasswords = [
        'wrongPassword',
        'correctPassword123',  // Missing !
        'correctPassword123!!', // Extra !
        'CorrectPassword123!', // Wrong case
        '',
        'password',
    ];

    foreach ($incorrectPasswords as $incorrectPassword) {
        try {
            $service->login('test@example.com', $incorrectPassword, $tenant->id);
            expect(false)->toBeTrue("Login should have failed for password: {$incorrectPassword}");
        } catch (InvalidCredentialsException $e) {
            expect(true)->toBeTrue();
        }
    }
})->group('auth-service', 'login');

test('user from tenant A cannot authenticate to tenant B', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);
    
    $userInTenantA = User::factory()->create([
        'email' => 'user@tenanta.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenantA->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Attempt to authenticate user from tenant A with tenant B context
    $service->login('user@tenanta.com', 'password123', $tenantB->id);
})->throws(InvalidCredentialsException::class)->group('auth-service', 'login', 'tenant-isolation');

test('users with same email in different tenants can login to their respective tenants', function () {
    // Set up Passport personal access client directly in database
    DB::table('oauth_clients')->insert([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'tenant_id' => null,
        'owner_type' => null,
        'owner_id' => null,
        'name' => 'Test Personal Access Client',
        'secret' => null,
        'provider' => 'users',
        'redirect_uris' => json_encode(['http://localhost']),
        'grant_types' => json_encode(['personal_access']),
        'revoked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);
    
    $userInTenantA = User::factory()->create([
        'email' => 'shared@example.com',
        'password' => Hash::make('passwordA'),
        'tenant_id' => $tenantA->id,
    ]);
    
    $userInTenantB = User::factory()->create([
        'email' => 'shared@example.com',
        'password' => Hash::make('passwordB'),
        'tenant_id' => $tenantB->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // User A can login to tenant A with their password
    $resultA = $service->login('shared@example.com', 'passwordA', $tenantA->id);
    expect($resultA)->toBeArray()
        ->and($resultA['access_token'])->not->toBeNull();

    // User B can login to tenant B with their password
    $resultB = $service->login('shared@example.com', 'passwordB', $tenantB->id);
    expect($resultB)->toBeArray()
        ->and($resultB['access_token'])->not->toBeNull();

    // User A cannot login to tenant B even with correct password
    try {
        $service->login('shared@example.com', 'passwordA', $tenantB->id);
        expect(false)->toBeTrue('Should have thrown exception');
    } catch (InvalidCredentialsException $e) {
        expect(true)->toBeTrue();
    }

    // User B cannot login to tenant A even with correct password
    try {
        $service->login('shared@example.com', 'passwordB', $tenantA->id);
        expect(false)->toBeTrue('Should have thrown exception');
    } catch (InvalidCredentialsException $e) {
        expect(true)->toBeTrue();
    }
})->group('auth-service', 'login', 'tenant-isolation');

test('requestPasswordReset stores token for valid user', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Create a tenant first to satisfy foreign key constraint
    $tenant = Tenant::factory()->create(['id' => 1]);

    $user = User::factory()->make([
        'id' => 1,
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
    ]);

    $userRepo->shouldReceive('findByEmail')
        ->with('test@example.com')
        ->andReturn($user);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $result = $service->requestPasswordReset('test@example.com', $tenant->id);

    expect($result)->toBeTrue();
    
    // Verify token was stored in database
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
    ]);
})->group('password-reset');

test('requestPasswordReset returns true for non-existent user to prevent enumeration', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $userRepo->shouldReceive('findByEmail')
        ->with('nonexistent@example.com')
        ->andReturn(null);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $result = $service->requestPasswordReset('nonexistent@example.com', 1);

    // Should return true even though user doesn't exist (Requirement 12.5)
    expect($result)->toBeTrue();
    
    // Verify no token was stored
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'nonexistent@example.com',
    ]);
})->group('password-reset');

test('requestPasswordReset returns true for wrong tenant to prevent enumeration', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $user = User::factory()->make([
        'id' => 1,
        'email' => 'test@example.com',
        'tenant_id' => 1,
    ]);

    $userRepo->shouldReceive('findByEmail')
        ->with('test@example.com')
        ->andReturn($user);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $result = $service->requestPasswordReset('test@example.com', 2);

    // Should return true even though tenant doesn't match (Requirement 12.5)
    expect($result)->toBeTrue();
    
    // Verify no token was stored for wrong tenant
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'test@example.com',
        'tenant_id' => 2,
    ]);
})->group('password-reset');

test('resetPassword updates password with valid token', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Create a tenant first to satisfy foreign key constraint
    $tenant = Tenant::factory()->create();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('oldpassword'),
        'tenant_id' => $tenant->id,
    ]);

    // Create a reset token
    $plainToken = 'reset-token-123';
    $hashedToken = Hash::make($plainToken);
    
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
        'token' => $hashedToken,
        'created_at' => now(),
    ]);

    $userRepo->shouldReceive('findByEmail')
        ->with('test@example.com')
        ->andReturn($user);

    $userRepo->shouldReceive('update')
        ->once()
        ->with($user, Mockery::on(function ($data) {
            return isset($data['password']) && Hash::check('newpassword', $data['password']);
        }))
        ->andReturn($user);

    $tokenRepo->shouldReceive('revokeAllForUser')
        ->once()
        ->with($user->id)
        ->andReturn(1);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $result = $service->resetPassword($plainToken, 'test@example.com', 'newpassword');

    expect($result)->toBeTrue();
    
    // Verify token was deleted
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
    ]);
})->group('password-reset');

test('resetPassword throws exception for invalid token', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Create a tenant first to satisfy foreign key constraint
    $tenant = Tenant::factory()->create();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('oldpassword'),
        'tenant_id' => $tenant->id,
    ]);

    // Create a reset token
    $hashedToken = Hash::make('correct-token');
    
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
        'token' => $hashedToken,
        'created_at' => now(),
    ]);

    $userRepo->shouldReceive('findByEmail')
        ->with('test@example.com')
        ->andReturn($user);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Try to reset with wrong token
    $service->resetPassword('wrong-token', 'test@example.com', 'newpassword');
})->throws(App\Exceptions\Auth\InvalidResetTokenException::class)->group('password-reset');

test('resetPassword throws exception for expired token', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Create a tenant first to satisfy foreign key constraint
    $tenant = Tenant::factory()->create();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('oldpassword'),
        'tenant_id' => $tenant->id,
    ]);

    // Create an expired reset token (61 minutes old, default expiration is 60)
    $plainToken = 'reset-token-123';
    $hashedToken = Hash::make($plainToken);
    
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
        'token' => $hashedToken,
        'created_at' => now()->subMinutes(61),
    ]);

    $userRepo->shouldReceive('findByEmail')
        ->with('test@example.com')
        ->andReturn($user);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $service->resetPassword($plainToken, 'test@example.com', 'newpassword');
})->throws(App\Exceptions\Auth\InvalidResetTokenException::class, 'expired')->group('password-reset');

test('resetPassword throws exception for non-existent user', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $userRepo->shouldReceive('findByEmail')
        ->with('nonexistent@example.com')
        ->andReturn(null);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $service->resetPassword('some-token', 'nonexistent@example.com', 'newpassword');
})->throws(App\Exceptions\Auth\InvalidResetTokenException::class)->group('password-reset');

test('resetPassword throws exception when no token record exists', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Create a tenant first to satisfy foreign key constraint
    $tenant = Tenant::factory()->create();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('oldpassword'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo->shouldReceive('findByEmail')
        ->with('test@example.com')
        ->andReturn($user);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // No token record in database
    $service->resetPassword('some-token', 'test@example.com', 'newpassword');
})->throws(App\Exceptions\Auth\InvalidResetTokenException::class)->group('password-reset');

test('resetPassword revokes all user tokens after successful reset', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Create a tenant first to satisfy foreign key constraint
    $tenant = Tenant::factory()->create();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('oldpassword'),
        'tenant_id' => $tenant->id,
    ]);

    // Create a reset token
    $plainToken = 'reset-token-123';
    $hashedToken = Hash::make($plainToken);
    
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
        'token' => $hashedToken,
        'created_at' => now(),
    ]);

    $userRepo->shouldReceive('findByEmail')
        ->with('test@example.com')
        ->andReturn($user);

    $userRepo->shouldReceive('update')
        ->once()
        ->andReturn($user);

    // Verify that revokeAllForUser is called (Requirement 13.5)
    $tokenRepo->shouldReceive('revokeAllForUser')
        ->once()
        ->with($user->id)
        ->andReturn(2); // Simulate 2 tokens revoked

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    $result = $service->resetPassword($plainToken, 'test@example.com', 'newpassword');

    expect($result)->toBeTrue();
})->group('password-reset');

// Task 8.3: Test token refresh
// **Property 9: Token Refresh Validity**
// **Validates: Requirements 3.1, 3.2, 3.3, 3.5**

test('refreshToken with valid refresh token issues new access token', function () {
    // Set up Passport personal access client directly in database
    DB::table('oauth_clients')->insert([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'tenant_id' => null,
        'owner_type' => null,
        'owner_id' => null,
        'name' => 'Test Personal Access Client',
        'secret' => null,
        'provider' => 'users',
        'redirect_uris' => json_encode(['http://localhost']),
        'grant_types' => json_encode(['personal_access']),
        'revoked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create tenant and user in database
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = new \App\Repositories\Eloquent\EloquentUserRepository();
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Mock a valid, non-revoked, non-expired token
    $mockToken = Mockery::mock(\Laravel\Passport\Token::class);
    $mockToken->shouldReceive('getAttribute')->with('user_id')->andReturn($user->id);
    $mockToken->shouldReceive('getAttribute')->with('revoked')->andReturn(false);
    $mockToken->shouldReceive('getAttribute')->with('expires_at')->andReturn(now()->addDay());
    $mockToken->shouldReceive('getAttribute')->with('id')->andReturn('old-token-id');

    $tokenRepo->shouldReceive('findByRefreshToken')
        ->with('valid-refresh-token')
        ->andReturn($mockToken);

    $tokenRepo->shouldReceive('revoke')
        ->once()
        ->with($mockToken)
        ->andReturn(true);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Refresh the token
    $result = $service->refreshToken('valid-refresh-token');

    // Verify new token structure
    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['access_token', 'refresh_token', 'token_type', 'expires_in'])
        ->and($result['token_type'])->toBe('Bearer')
        ->and($result['access_token'])->not->toBeNull();
})->group('auth-service', 'token-refresh');

test('refreshToken with expired refresh token fails', function () {
    // Create tenant and user in database
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Mock an expired token
    $expiredToken = Mockery::mock(\Laravel\Passport\Token::class);
    $expiredToken->shouldReceive('getAttribute')->with('user_id')->andReturn($user->id);
    $expiredToken->shouldReceive('getAttribute')->with('revoked')->andReturn(false);
    $expiredToken->shouldReceive('getAttribute')->with('expires_at')->andReturn(now()->subDay());

    $tokenRepo->shouldReceive('findByRefreshToken')
        ->with('expired-refresh-token')
        ->andReturn($expiredToken);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Attempt to refresh with expired token should fail
    $service->refreshToken('expired-refresh-token');
})->throws(InvalidCredentialsException::class, 'expired')->group('auth-service', 'token-refresh');

test('refreshToken with revoked refresh token fails', function () {
    // Create tenant and user in database
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'tenant_id' => $tenant->id,
    ]);

    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    // Mock a revoked token
    $revokedToken = Mockery::mock(\Laravel\Passport\Token::class);
    $revokedToken->shouldReceive('getAttribute')->with('user_id')->andReturn($user->id);
    $revokedToken->shouldReceive('getAttribute')->with('revoked')->andReturn(true);
    $revokedToken->shouldReceive('getAttribute')->with('expires_at')->andReturn(now()->addDay());

    $tokenRepo->shouldReceive('findByRefreshToken')
        ->with('revoked-refresh-token')
        ->andReturn($revokedToken);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Attempt to refresh with revoked token should fail
    $service->refreshToken('revoked-refresh-token');
})->throws(InvalidCredentialsException::class, 'revoked')->group('auth-service', 'token-refresh');

test('refreshToken with invalid refresh token fails', function () {
    $userRepo = Mockery::mock(UserRepositoryInterface::class);
    $tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
    $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

    $tokenRepo->shouldReceive('findByRefreshToken')
        ->with('invalid-refresh-token')
        ->andReturn(null);

    $service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

    // Attempt to refresh with invalid token should fail
    $service->refreshToken('invalid-refresh-token');
})->throws(InvalidCredentialsException::class, 'Invalid refresh token')->group('auth-service', 'token-refresh');
