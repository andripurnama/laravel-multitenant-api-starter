# Test Migration Guide for Sanctum

## Overview
After migrating from Passport to Sanctum, several test files need to be updated to reflect the new authentication system.

## Key Changes Required

### 1. Remove TokenRepositoryInterface Dependency

**Files Affected:**
- `tests/Unit/Services/AuthServiceTest.php`
- `tests/Unit/Services/AuthServiceEmailVerificationTest.php`

**Change:**
```php
// Before
$userRepo = Mockery::mock(UserRepositoryInterface::class);
$tokenRepo = Mockery::mock(TokenRepositoryInterface::class);
$tenantRepo = Mockery::mock(TenantRepositoryInterface::class);
$service = new AuthService($userRepo, $tokenRepo, $tenantRepo);

// After
$userRepo = Mockery::mock(UserRepositoryInterface::class);
$tenantRepo = Mockery::mock(TenantRepositoryInterface::class);
$service = new AuthService($userRepo, $tenantRepo);
```

### 2. Remove TokenRepositoryInterface Import

```php
// Remove this line from test files:
use App\Repositories\Contracts\TokenRepositoryInterface;
```

### 3. Update Login Tests

**Change token response expectations:**
```php
// Before (Passport)
expect($result)->toBeArray()
    ->and($result)->toHaveKeys(['access_token', 'refresh_token', 'token_type', 'expires_in'])
    ->and($result['token_type'])->toBe('Bearer')
    ->and($result['access_token'])->not->toBeNull()
    ->and($result['refresh_token'])->not->toBeNull();

// After (Sanctum)
expect($result)->toBeArray()
    ->and($result)->toHaveKeys(['access_token', 'token_type'])
    ->and($result['token_type'])->toBe('Bearer')
    ->and($result['access_token'])->not->toBeNull();
```

### 4. Remove Passport OAuth Client Setup

**Remove these blocks from login tests:**
```php
// Remove this entire block:
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
```

### 5. Remove All Refresh Token Tests

**Delete these test cases entirely:**
- `refreshToken with valid refresh token issues new access token`
- `refreshToken with expired refresh token fails`
- `refreshToken with revoked refresh token fails`
- `refreshToken with invalid refresh token fails`

### 6. Update Password Reset Token Revocation Tests

**Change:**
```php
// Before (using TokenRepository)
$tokenRepo->shouldReceive('revokeAllForUser')
    ->once()
    ->with($user->id)
    ->andReturn(2);

// After (using Sanctum directly)
// Create some tokens first
$user->createToken('token1');
$user->createToken('token2');

// Then in the test, verify they were deleted
expect($user->fresh()->tokens)->toHaveCount(0);
```

### 7. Update TokenRepositoryTest

Already updated to test Sanctum's token functionality instead of Passport.

## Quick Fix Script

To quickly update all AuthService instantiations in tests, run this find-and-replace:

### Find:
```
new AuthService\(\$userRepo, \$tokenRepo, \$tenantRepo\)
```

### Replace with:
```
new AuthService($userRepo, $tenantRepo)
```

### Also find:
```
\$tokenRepo = Mockery::mock\(TokenRepositoryInterface::class\);
```

### And remove those lines.

## Running Tests After Migration

1. **Clear configuration cache:**
   ```bash
   php artisan config:clear
   ```

2. **Run migrations:**
   ```bash
   php artisan migrate:fresh
   ```

3. **Run specific test groups:**
   ```bash
   # Test authentication
   php artisan test --group=auth-service
   
   # Test password reset
   php artisan test --group=password-reset
   
   # Test repositories
   php artisan test tests/Unit/Repositories/
   ```

4. **Run all tests:**
   ```bash
   composer test
   ```

## Expected Test Results

After proper migration:
- ✅ All registration tests should pass (no changes needed)
- ✅ All login tests should pass (after removing OAuth client setup and updating expectations)
- ❌ All refresh token tests should be removed
- ✅ Password reset tests should pass (after updating token revocation)
- ✅ Token repository tests should pass (already updated)

## Manual Test Updates Required

Due to the size and complexity of the test files, here are the specific sections that need manual updates:

### AuthServiceTest.php

1. **Lines 1-50**: Update all `new AuthService()` calls to remove `$tokenRepo` parameter
2. **Lines 180-220**: Remove OAuth client setup from login tests
3. **Lines 220-250**: Update login response expectations
4. **Lines 740-900**: Remove all `refreshToken` test cases
5. **Lines 580-650**: Update password reset tests to use Sanctum token deletion

### AuthServiceEmailVerificationTest.php

1. **Lines 1-20**: Remove `TokenRepositoryInterface` import and mock
2. **Lines 20-100**: Update all `new AuthService()` calls

## Verification Checklist

- [ ] All `TokenRepositoryInterface` imports removed
- [ ] All `$tokenRepo` mocks removed
- [ ] All `AuthService` constructor calls updated
- [ ] All OAuth client setup code removed
- [ ] All refresh token tests removed
- [ ] Login response expectations updated
- [ ] Password reset token revocation updated
- [ ] Tests run successfully

## Need Help?

If tests are still failing after these changes:
1. Check the error message for specific line numbers
2. Verify the AuthService constructor signature matches the test calls
3. Ensure no Passport-specific code remains in tests
4. Check that Sanctum migrations have been run
