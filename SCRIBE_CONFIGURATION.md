# Scribe API Documentation Configuration

This document explains how the Scribe API documentation is configured for this multi-tenant API.

## Configuration File

The main configuration is in `config/scribe.php`.

## Custom Headers

### X-Tenant-ID Header

All API endpoints require the `X-Tenant-ID` header. This is configured in the `strategies.headers` section:

```php
'headers' => [
    ...Defaults::HEADERS_STRATEGIES,
    Strategies\StaticData::withSettings(data: [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'X-Tenant-ID' => '1',  // Added for multi-tenant support
    ]),
],
```

This ensures that:
- All example requests in the documentation include `X-Tenant-ID: 1`
- The "Try It Out" feature automatically includes this header
- Postman collection includes this header in all requests
- OpenAPI spec documents this required header

## Introduction Text

The introduction section explains the multi-tenant architecture to API consumers:

```php
'intro_text' => <<<'INTRO'
    // ... standard intro text ...

    ## Multi-Tenant Architecture

    **Important:** All API requests must include the `X-Tenant-ID` header to specify the tenant context.

    ```
    X-Tenant-ID: 1
    ```

    Without this header, requests will fail with a 400 error: `{"error": "Tenant ID required"}`

    ### Test Tenant
    A test tenant is available for development:
    - **Tenant ID**: 1
    - **Tenant Name**: Test Tenant
    - **Tenant Slug**: test-tenant

    Use `X-Tenant-ID: 1` in all your API requests during testing.
INTRO,
```

## Regenerating Documentation

After making changes to:
- Route definitions
- Request validation rules
- Controller methods
- Scribe configuration

Run this command to regenerate the documentation:

```bash
php artisan scribe:generate
```

## Viewing Documentation

The documentation is available at:
- **HTML Docs**: `http://localhost:8000/docs`
- **Postman Collection**: `http://localhost:8000/docs.postman`
- **OpenAPI Spec**: `http://localhost:8000/docs.openapi`

Or if using Laravel Valet/Herd:
- `http://your-app.test/docs`

## Try It Out Feature

The "Try It Out" feature is enabled in the documentation, allowing users to test endpoints directly from the browser:

```php
'try_it_out' => [
    'enabled' => true,
    'base_url' => null, // Uses the same base URL as displayed
],
```

**Important**: Make sure CORS is properly configured if you want external users to use this feature.

## Adding More Custom Headers

To add additional custom headers (e.g., `X-API-Version`, `X-Request-ID`), update the `strategies.headers` section:

```php
'headers' => [
    ...Defaults::HEADERS_STRATEGIES,
    Strategies\StaticData::withSettings(data: [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'X-Tenant-ID' => '1',
        'X-API-Version' => 'v1',  // Example additional header
    ]),
],
```

## Per-Endpoint Header Customization

To customize headers for specific endpoints, use PHPDoc annotations in your controller:

```php
/**
 * Register a new user
 * 
 * @header X-Tenant-ID 1
 * @header X-Custom-Header custom-value
 */
public function register(RegisterRequest $request): JsonResponse
{
    // ...
}
```

## Authentication Documentation

If you need to document authentication (Bearer tokens), update the `auth` section:

```php
'auth' => [
    'enabled' => true,
    'default' => false,  // Set to true if most endpoints require auth
    'in' => AuthIn::BEARER->value,
    'name' => 'Authorization',
    'placeholder' => '{YOUR_ACCESS_TOKEN}',
    'extra_info' => 'You can retrieve your token by logging in via the `/api/auth/login` endpoint.',
],
```

Then mark protected endpoints with `@authenticated` in their PHPDoc:

```php
/**
 * Get user profile
 * 
 * @authenticated
 */
public function profile(Request $request): UserResource
{
    // ...
}
```

## Grouping Endpoints

To organize endpoints into logical groups, use the `@group` annotation:

```php
/**
 * Register a new user
 * 
 * @group Authentication
 */
public function register(RegisterRequest $request): JsonResponse
{
    // ...
}
```

## Response Examples

Scribe automatically generates response examples by:
1. Making actual API calls (for GET requests)
2. Using API Resources/Transformers
3. Using `@response` annotations

To add custom response examples:

```php
/**
 * Register a new user
 * 
 * @response 201 {
 *   "data": {
 *     "id": 1,
 *     "name": "John Doe",
 *     "email": "john@example.com",
 *     "created_at": "2026-04-21T08:37:16.000000Z"
 *   }
 * }
 * 
 * @response 400 {
 *   "error": "Tenant ID required"
 * }
 */
public function register(RegisterRequest $request): JsonResponse
{
    // ...
}
```

## Best Practices

1. **Always regenerate docs** after API changes
2. **Use descriptive PHPDoc comments** for better documentation
3. **Test the "Try It Out" feature** to ensure it works correctly
4. **Keep intro text updated** with current tenant information
5. **Document all required headers** in the configuration
6. **Use groups** to organize endpoints logically
7. **Add response examples** for common error cases

## Troubleshooting

### Headers not showing in docs
- Check `config/scribe.php` headers section
- Run `php artisan config:clear`
- Regenerate docs with `php artisan scribe:generate`

### Try It Out not working
- Check CORS configuration
- Verify `base_url` is correct
- Ensure API is accessible from the browser

### Changes not reflected
- Clear config cache: `php artisan config:clear`
- Clear route cache: `php artisan route:clear`
- Regenerate docs: `php artisan scribe:generate`

## Resources

- [Scribe Documentation](https://scribe.knuckles.wtf/laravel/)
- [Scribe Configuration Reference](https://scribe.knuckles.wtf/laravel/reference/config)
- [Documenting Your API](https://scribe.knuckles.wtf/laravel/documenting/)
