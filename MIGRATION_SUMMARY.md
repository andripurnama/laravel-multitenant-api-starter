# Laravel Sanctum Migration Summary

## ✅ Completed Changes

### 1. Dependencies
- ✅ Removed `laravel/passport` from composer.json
- ✅ Added `laravel/sanctum` to composer.json
- ✅ Ran `composer install` successfully

### 2. Configuration Files
- ✅ Updated `config/auth.php` - Changed API guard driver from 'passport' to 'sanctum'
- ✅ Created `config/sanctum.php` - New Sanctum configuration
- ✅ Removed `config/passport.php` - No longer needed
- ✅ Updated `.env.example` - Added Sanctum configuration variables

### 3. Models
- ✅ Updated `app/Models/User.php` - Changed from `Laravel\Passport\HasApiTokens` to `Laravel\Sanctum\HasApiTokens`

### 4. Services
- ✅ Updated `app/Services/AuthService.php`:
  - Removed `TokenRepositoryInterface` dependency
  - Removed `refreshToken()` method
  - Updated `login()` to return Sanctum token
  - Updated `logout()` to use Sanctum's token deletion
  - Updated `resetPassword()` to use Sanctum's token revocation

- ✅ Updated `app/Services/TokenService.php`:
  - Changed return type from `PersonalAccessTokenResult` to `NewAccessToken`
  - Updated parameter names from `scopes` to `abilities`
  - Removed `TokenRepositoryInterface` dependency

### 5. Service Contracts
- ✅ Updated `app/Services/Contracts/AuthServiceInterface.php`:
  - Removed `refreshToken()` method signature
  - Updated `login()` return type documentation

- ✅ Updated `app/Services/Contracts/TokenServiceInterface.php`:
  - Changed return type from `PersonalAccessTokenResult` to `NewAccessToken`
  - Updated parameter names

### 6. Controllers
- ✅ Updated `app/Http/Controllers/AuthController.php`:
  - Removed `refresh()` method
  - Updated `login()` response (removed refresh_token and expires_in)

### 7. Routes
- ✅ Updated `routes/api.php` - Removed `/api/auth/refresh` endpoint

### 8. Providers
- ✅ Updated `app/Providers/AppServiceProvider.php`:
  - Removed Passport import
  - Removed Passport token expiration configuration

### 9. Database Migrations
- ✅ Created `create_personal_access_tokens_table.php` - Sanctum's token table
- ✅ Removed all Passport OAuth migration files (oauth_auth_codes, oauth_access_tokens, etc.)

### 10. Documentation
- ✅ Updated `API_USAGE.md` - Reflected new token format and removed refresh endpoint
- ✅ Created `SANCTUM_MIGRATION.md` - Comprehensive migration guide
- ✅ Created `TEST_MIGRATION_GUIDE.md` - Guide for updating tests
- ✅ Created this summary document

### 11. Tests
- ✅ Updated `tests/Unit/Repositories/TokenRepositoryTest.php` - Now tests Sanctum token functionality

## ⚠️ Remaining Tasks

### Tests Need Manual Updates
The following test files contain many references to the old Passport system and need to be updated:

1. **`tests/Unit/Services/AuthServiceTest.php`** (1295 lines)
   - Remove all `TokenRepositoryInterface` mocks
   - Update all `AuthService` constructor calls (remove `$tokenRepo` parameter)
   - Remove OAuth client setup code from login tests
   - Update login response expectations (no refresh_token, no expires_in)
   - Remove all refresh token test cases
   - Update password reset tests to use Sanctum token deletion

2. **`tests/Unit/Services/AuthServiceEmailVerificationTest.php`**
   - Remove `TokenRepositoryInterface` import and mocks
   - Update all `AuthService` constructor calls

### How to Update Tests

**Option 1: Automated Find & Replace**
```bash
# In your IDE, use find & replace with regex:
# Find: new AuthService\(\$userRepo, \$tokenRepo, \$tenantRepo\)
# Replace: new AuthService($userRepo, $tenantRepo)

# Also find and remove:
# \$tokenRepo = Mockery::mock\(TokenRepositoryInterface::class\);
```

**Option 2: Manual Updates**
Follow the detailed guide in `TEST_MIGRATION_GUIDE.md`

## 🚀 Next Steps

1. **Update Test Files**:
   ```bash
   # Edit these files manually or with find & replace:
   tests/Unit/Services/AuthServiceTest.php
   tests/Unit/Services/AuthServiceEmailVerificationTest.php
   ```

2. **Run Database Migrations**:
   ```bash
   php artisan migrate:fresh
   ```

3. **Run Tests**:
   ```bash
   composer test
   ```

4. **Verify API Endpoints**:
   ```bash
   # Start server
   php artisan serve
   
   # Test registration
   curl -X POST http://localhost:8000/api/auth/register \
     -H "Content-Type: application/json" \
     -H "X-Tenant-ID: 1" \
     -d '{"name":"Test","email":"test@example.com","password":"password123"}'
   
   # Test login
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -H "X-Tenant-ID: 1" \
     -d '{"email":"test@example.com","password":"password123"}'
   ```

## 📋 Breaking Changes for API Consumers

### Token Format
- **Before**: JWT tokens (e.g., `eyJ0eXAiOiJKV1QiLCJhbGc...`)
- **After**: Plain text tokens (e.g., `1|abcdefghijklmnopqrstuvwxyz...`)

### Login Response
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

### Removed Endpoints
- `POST /api/auth/refresh` - No longer available

### Token Expiration
- **Before**: Tokens expired after 15 minutes by default
- **After**: Tokens never expire by default (configurable via `SANCTUM_EXPIRATION`)

## 📚 Additional Resources

- **Sanctum Documentation**: https://laravel.com/docs/sanctum
- **Migration Guide**: See `SANCTUM_MIGRATION.md`
- **Test Updates**: See `TEST_MIGRATION_GUIDE.md`
- **API Usage**: See `API_USAGE.md`

## ✨ Benefits of Sanctum

1. **Simpler**: No OAuth2 complexity for first-party applications
2. **Lighter**: Fewer database tables and dependencies
3. **Faster**: No JWT encoding/decoding overhead
4. **Flexible**: Easy to customize token abilities/permissions
5. **Modern**: Better suited for SPA and mobile app authentication

## 🔄 Rollback Plan

If you need to rollback to Passport:
1. Restore database backup
2. Revert all code changes (use git)
3. Run `composer require laravel/passport`
4. Run `composer remove laravel/sanctum`
5. Restore configuration files
6. Run migrations

## ✅ Verification Checklist

- [x] Passport removed from composer.json
- [x] Sanctum added to composer.json
- [x] Auth config updated to use Sanctum
- [x] User model uses Sanctum's HasApiTokens
- [x] AuthService updated (no refresh tokens)
- [x] TokenService updated
- [x] Controllers updated
- [x] Routes updated (refresh endpoint removed)
- [x] AppServiceProvider updated
- [x] Sanctum migration created
- [x] Passport migrations removed
- [x] Documentation updated
- [ ] Tests updated (IN PROGRESS)
- [ ] Database migrated
- [ ] API endpoints tested
- [ ] All tests passing

## 📞 Support

For issues or questions:
1. Check `SANCTUM_MIGRATION.md` for detailed migration steps
2. Check `TEST_MIGRATION_GUIDE.md` for test update guidance
3. Review Laravel Sanctum documentation
4. Check the test output for specific errors
