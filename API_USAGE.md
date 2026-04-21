# API Usage Guide

## Multi-Tenant Authentication

This API uses **header-based multi-tenancy**. All API requests must include the `X-Tenant-ID` header to specify which tenant context the request belongs to.

### Important: Tenant ID Header

**All API endpoints require the `X-Tenant-ID` header.**

```
X-Tenant-ID: 1
```

Without this header, you'll receive:
```json
{
  "error": "Tenant ID required"
}
```

## Getting Started

### 1. Check Available Tenants

After running migrations and seeders, a test tenant is created:

```bash
php artisan migrate:fresh --seed
```

This creates:
- **Tenant ID**: 1
- **Tenant Name**: Test Tenant
- **Tenant Slug**: test-tenant

### 2. Register a New User

**Endpoint**: `POST /api/auth/register`

**Headers**:
```
Content-Type: application/json
X-Tenant-ID: 1
```

**Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Response** (201 Created):
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-04-21T08:37:16.000000Z"
  }
}
```

### 3. Login

**Endpoint**: `POST /api/auth/login`

**Headers**:
```
Content-Type: application/json
X-Tenant-ID: 1
```

**Body**:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response**:
```json
{
  "access_token": "1|abcdefghijklmnopqrstuvwxyz...",
  "token_type": "Bearer"
}
```

**Note**: Sanctum tokens are long-lived and don't expire by default. You can configure expiration in the `.env` file using `SANCTUM_EXPIRATION` (in minutes).

### 4. Access Protected Endpoints

Use the access token in the Authorization header:

**Headers**:
```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz...
X-Tenant-ID: 1
```

## API Endpoints

### Public Endpoints (No Authentication Required)

| Method | Endpoint | Description | Requires X-Tenant-ID |
|--------|----------|-------------|---------------------|
| POST | `/api/auth/register` | Register new user | ✅ |
| POST | `/api/auth/login` | Login user | ✅ |
| POST | `/api/auth/password/reset-request` | Request password reset | ✅ |
| POST | `/api/auth/password/reset` | Reset password | ✅ |
| GET | `/api/auth/email/verify/{token}` | Verify email | ✅ |

### Protected Endpoints (Authentication Required)

| Method | Endpoint | Description | Requires X-Tenant-ID |
|--------|----------|-------------|---------------------|
| POST | `/api/auth/logout` | Logout user | ✅ |
| GET | `/api/auth/profile` | Get user profile | ✅ |
| POST | `/api/auth/email/verify-send` | Send verification email | ✅ |
| POST | `/api/permissions/assign-role` | Assign role to user | ✅ |
| POST | `/api/permissions/remove-role` | Remove role from user | ✅ |
| POST | `/api/permissions/assign-permission` | Assign permission to role | ✅ |
| POST | `/api/roles` | Create new role | ✅ |
| POST | `/api/roles/{role}/permissions` | Sync role permissions | ✅ |

## Testing with cURL

### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: 1" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: 1" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Get Profile
```bash
curl -X GET http://localhost:8000/api/auth/profile \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "X-Tenant-ID: 1"
```

## Testing with Postman

1. Create a new request
2. Add header: `X-Tenant-ID: 1`
3. For protected endpoints, add header: `Authorization: Bearer YOUR_ACCESS_TOKEN`
4. Set request body to JSON format

## Multi-Tenant Isolation

- Each tenant has isolated data
- Users belong to a specific tenant
- Cross-tenant access is prevented by middleware
- The `X-Tenant-ID` header determines the tenant context for all operations

## Creating Additional Tenants

```bash
php artisan tinker
```

```php
App\Models\Tenant::create([
    'name' => 'Another Company',
    'slug' => 'another-company'
]);
```

## Common Errors

### Missing Tenant Header
```json
{
  "error": "Tenant ID required"
}
```
**Solution**: Add `X-Tenant-ID` header to your request

### Invalid Tenant
```json
{
  "error": "Tenant {id} not found"
}
```
**Solution**: Use a valid tenant ID that exists in the database

### Invalid Credentials
```json
{
  "error": "Invalid credentials"
}
```
**Solution**: Check email/password combination and ensure user exists in the specified tenant

## Development Server

Start the development server:
```bash
php artisan serve
```

The API will be available at: `http://localhost:8000`
