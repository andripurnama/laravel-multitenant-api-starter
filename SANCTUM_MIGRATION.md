# Laravel Sanctum Migration Guide

This document outlines the migration from Laravel Passport to Laravel Sanctum for API authentication.

## What Changed

### 1. Authentication Package
- **Before**: Laravel Passport (OAuth2 server)
- **After**: Laravel Sanctum (Simple token-based authentication)

### 2. Token Management
- **Before**: OAuth2 access tokens + refresh tokens with expiration
- **After**: Simple personal access tokens (long-lived by default)

### 3. Key Differences

| Feature | Passport | Sanctum |
|---------|----------|---------|
| Token Type | JWT (OAuth2) | Plain text tokens (hashed in DB) |
| Refresh Tokens | ✅ Yes | ❌ No (tokens are long-lived) |
| Token Expiration | Configurable (default: 15 min) | Optional (default: never expires) |
| Complexity | High (full OAuth2 server) | Low (simple token auth) |
| Use Case | Third-party OAuth clients | First-party SPA/mobile apps |

## Changes Made

### 1. Dependencies
```bash
# Removed
composer remove laravel/passport

# Added (already included in Laravel 13)
laravel/sanctum: ^4.0
```

### 2. Configuration Files

#### Updated: `config/auth.php`
```php
'guards' => [
    'api' => [
        'driver' => 'sanctum',  // Changed from 'passport'
        'provider' => 'users',
    ],
],
```

#### Added: `config/sanctum.php`
New configuration file for Sanctum settings including:
- Stateful domains
- Token expiration
- Token prefix
- Middleware configuration

#### Removed: `config/passport.php`
No longer needed with Sanctum.

### 3. Database Migrations

#### Added
- `create_personal_access_tokens_table.php` - Sanctum's token storage

#### Removed
All Passport OAuth2 tables:
- `oauth_auth_codes`
- `oauth_access_tokens`
- `oauth_refresh_tokens`
- `oauth_clients`
- `oauth_device_codes`

### 4. Model Changes

#### `app/Models/User.php`
```php
// Before
use Laravel\Passport\HasApiTokens;

// After
use Laravel\Sanctum\HasApiTokens;
```

### 5. Service Layer Changes

#### `app/Services/AuthService.php`
- **Removed**: `refreshToken()` method (Sanctum doesn't use refresh tokens)
- **Updated**: `login()` - Now returns simple token instead of OAuth2 response
- **Updated**: `logout()` - Uses Sanctum's token deletion
- **Updated**: `resetPassword()` - Uses Sanctum's token revocation

```php
// Before (Passport)
$tokenResult = $user->createToken('auth_token');
return [
    'access_token' => $tokenResult->accessToken,
    'refresh_token' => $refreshToken->id,
    'token_type' => 'Bearer',
    'expires_in' => $token->expires_at->diffInSeconds(now()),
];

// After (Sanctum)
$token = $user->createToken('auth_token');
return [
    'access_token' => $token->plainTextToken,
    'token_type' => 'Bearer',
];
```

#### `app/Services/TokenService.php`
- **Updated**: Return type changed from `PersonalAccessTokenResult` to `NewAccessToken`
- **Updated**: Parameter name changed from `scopes` to `abilities`
- **Updated**: Token revocation uses Sanctum's methods

### 6. Controller Changes

#### `app/Http/Controllers/AuthController.php`
- **Removed**: `refresh()` endpoint
- **Updated**: `login()` - Simplified response (no refresh token or expiration)
- **Updated**: `logout()` - Uses current access token deletion

### 7. Routes

#### `routes/api.php`
- **Removed**: `POST /api/auth/refresh` endpoint

### 8. Provider Changes

#### `app/Providers/AppServiceProvider.php`
- **Removed**: Passport token expiration configuration
- **Removed**: `use Laravel\Passport\Passport;`

### 9. Repository Changes

#### Token Repository
The `TokenRepositoryInterface` and `EloquentTokenRepository` are no longer used by AuthService since Sanctum provides direct token management through the User model.

## Migration Steps for Existing Projects

If you're migrating an existing project:

1. **Backup your database** before proceeding

2. **Install Sanctum**:
   ```bash
   composer require laravel/sanctum
   ```

3. **Remove Passport**:
   ```bash
   composer remove laravel/passport
   ```

4. **Update configuration files** as shown above

5. **Run new migration**:
   ```bash
   php artisan migrate
   ```

6. **Revoke all existing Passport tokens**:
   ```bash
   php artisan tinker
   DB::table('oauth_access_tokens')->update(['revoked' => true]);
   ```

7. **Update your code** following the changes outlined above

8. **Test authentication flow**:
   - Register new user
   - Login (get new Sanctum token)
   - Access protected endpoints
   - Logout

9. **Update API documentation** to reflect new token format

10. **Notify API consumers** about the breaking changes

## Breaking Changes for API Consumers

### 1. Token Format Changed
- **Before**: JWT tokens (e.g., `eyJ0eXAiOiJKV1QiLCJhbGc...`)
- **After**: Plain text tokens (e.g., `1|abcdefghijklmnopqrstuvwxyz...`)

### 2. Login Response Changed
```json
// Before
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200...",
  "token_type": "Bearer",
  "expires_in": 900
}

// After
{
  "access_token": "1|abcdefghijklmnopqrstuvwxyz...",
  "token_type": "Bearer"
}
```

### 3. Refresh Endpoint Removed
- The `/api/auth/refresh` endpoint no longer exists
- Tokens are long-lived by default
- Users must re-login when tokens expire (if expiration is configured)

### 4. Token Expiration
- **Before**: Tokens expired after 15 minutes by default
- **After**: Tokens never expire by default (configurable via `SANCTUM_EXPIRATION`)

## Configuration Options

### Token Expiration
To set token expiration, add to `.env`:
```env
SANCTUM_EXPIRATION=1440  # Expires after 24 hours (in minutes)
```

Or set to `null` for no expiration:
```env
SANCTUM_EXPIRATION=
```

### Stateful Domains
For SPA authentication, configure stateful domains in `.env`:
```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,yourdomain.com
```

## Testing

### Unit Tests
Update tests that mock Passport tokens to use Sanctum:

```php
// Before (Passport)
$token = Mockery::mock(Token::class);

// After (Sanctum)
$token = $user->createToken('test-token');
```

### Feature Tests
Update authentication in feature tests:

```php
// Before (Passport)
Passport::actingAs($user);

// After (Sanctum)
Sanctum::actingAs($user);
```

## Advantages of Sanctum

1. **Simpler**: No OAuth2 complexity for first-party applications
2. **Lighter**: Fewer database tables and dependencies
3. **Faster**: No JWT encoding/decoding overhead
4. **Flexible**: Easy to customize token abilities/permissions
5. **Modern**: Better suited for SPA and mobile app authentication

## When to Use Passport Instead

Consider staying with Passport if you need:
- Full OAuth2 server functionality
- Third-party application authorization
- Short-lived tokens with automatic refresh
- OAuth2 grant types (authorization code, client credentials, etc.)

## Support

For issues or questions about this migration:
1. Check Laravel Sanctum documentation: https://laravel.com/docs/sanctum
2. Review the changes in this document
3. Test the authentication flow thoroughly

## Rollback Plan

If you need to rollback to Passport:
1. Restore database backup
2. Revert code changes
3. Run `composer require laravel/passport`
4. Run `composer remove laravel/sanctum`
5. Restore configuration files
6. Run migrations
