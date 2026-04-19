# Project Structure

## Root Directory Layout

```
├── app/                    # Application core code
├── bootstrap/              # Framework bootstrap files
├── config/                 # Configuration files
├── database/               # Database migrations, factories, seeders
├── public/                 # Web server document root
├── resources/              # Views, raw assets (CSS, JS)
├── routes/                 # Route definitions
├── storage/                # Generated files, logs, cache
├── tests/                  # Automated tests
└── vendor/                 # Composer dependencies (not committed)
```

## Application Directory (`app/`)

Laravel follows MVC architecture with Service-Repository pattern:

```
app/
├── Http/
│   ├── Controllers/        # Request handlers (thin controllers)
│   │   └── Controller.php  # Base controller
│   ├── Requests/          # Form request validation
│   ├── Resources/         # API response transformers
│   └── Middleware/        # HTTP middleware
├── Models/                # Eloquent ORM models
│   └── User.php          # Default user model
├── Repositories/          # Data access layer
│   ├── Contracts/        # Repository interfaces
│   └── Eloquent/         # Eloquent implementations
├── Services/             # Business logic layer
├── Actions/              # Single-purpose action classes
├── Providers/            # Service providers
│   └── AppServiceProvider.php
└── Exceptions/           # Custom exceptions
```

### Service-Repository Pattern

**Repository Layer** (`app/Repositories/`)
- Abstracts data access logic
- Defines contracts (interfaces) in `Contracts/`
- Implements with Eloquent in `Eloquent/`
- Bound in service providers
- Example: `UserRepositoryInterface` → `EloquentUserRepository`

**Service Layer** (`app/Services/`)
- Contains business logic
- Orchestrates repositories and other services
- Keeps controllers thin
- Testable in isolation
- Example: `UserService`, `AuthService`, `TenantService`

**Actions** (`app/Actions/`)
- Single-purpose, focused operations
- Alternative to service methods for discrete tasks
- Example: `CreateUserAction`, `AssignTenantAction`

### Conventions
- **Models**: Singular, PascalCase (e.g., `User`, `BlogPost`)
- **Controllers**: PascalCase with `Controller` suffix (e.g., `UserController`)
- **Services**: PascalCase with `Service` suffix (e.g., `UserService`)
- **Repositories**: PascalCase with `Repository` suffix (e.g., `UserRepository`)
- **Interfaces**: PascalCase with `Interface` suffix (e.g., `UserRepositoryInterface`)
- **Actions**: PascalCase with `Action` suffix (e.g., `CreateUserAction`)
- **Namespaces**: Follow PSR-4, match directory structure (`App\Http\Controllers`)

## Database Directory (`database/`)

```
database/
├── factories/              # Model factories for testing
│   └── UserFactory.php
├── migrations/             # Database schema versions
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   └── 0001_01_01_000002_create_jobs_table.php
└── seeders/               # Database seeders
    └── DatabaseSeeder.php
```

### Migration Naming
- Format: `YYYY_MM_DD_HHMMSS_description.php`
- Use descriptive names: `create_users_table`, `add_status_to_posts`

## Routes Directory (`routes/`)

```
routes/
├── web.php                # Web routes (session, CSRF protection)
└── console.php            # Artisan console commands
```

### Route Organization
- `web.php` - Browser-facing routes with session/CSRF middleware
- API routes can be added via `api.php` (not present by default in Laravel 13)
- Console commands defined in `console.php`

## Resources Directory (`resources/`)

```
resources/
├── css/
│   └── app.css            # Main stylesheet (Tailwind)
├── js/
│   └── app.js             # Main JavaScript entry
└── views/
    └── welcome.blade.php  # Blade templates
```

### Frontend Assets
- CSS/JS in `resources/` are compiled by Vite
- Compiled assets output to `public/build/`
- Use `@vite()` directive in Blade templates

## Tests Directory (`tests/`)

```
tests/
├── Feature/               # Feature/integration tests
│   └── ExampleTest.php
├── Unit/                  # Unit tests
│   └── ExampleTest.php
├── Pest.php              # Pest configuration
└── TestCase.php          # Base test case
```

### Testing Conventions
- **Test-Driven Development (TDD)**: Write tests BEFORE implementation
- **Feature tests**: Test complete features, HTTP requests, database interactions
- **Unit tests**: Test individual classes/methods in isolation
- **Repository tests**: Test data access logic with database
- **Service tests**: Test business logic with mocked repositories
- **Use Pest syntax (preferred)**: `test()` or `it()` functions
- **Database**: Use `RefreshDatabase` trait for clean state
- **Mocking**: Use Mockery for mocking dependencies in unit tests
- **Coverage**: Aim for high test coverage on services and repositories
- **Naming**: Descriptive test names that explain behavior

### TDD Workflow
1. Write a failing test (Red)
2. Write minimal code to pass (Green)
3. Refactor while keeping tests green (Refactor)
4. Repeat

### Test Structure Example
```php
// Feature test
test('user can be created via api', function () {
    $data = ['name' => 'John', 'email' => 'john@example.com'];
    
    $response = $this->postJson('/api/users', $data);
    
    $response->assertCreated();
    $this->assertDatabaseHas('users', $data);
});

// Unit test with mocking
test('user service creates user through repository', function () {
    $repository = Mockery::mock(UserRepositoryInterface::class);
    $repository->shouldReceive('create')
        ->once()
        ->with(['name' => 'John'])
        ->andReturn(new User(['name' => 'John']));
    
    $service = new UserService($repository);
    $user = $service->create(['name' => 'John']);
    
    expect($user->name)->toBe('John');
});
```

## Configuration Directory (`config/`)

Key configuration files:
- `app.php` - Application settings, service providers
- `database.php` - Database connections
- `auth.php` - Authentication configuration
- `cache.php` - Cache stores
- `queue.php` - Queue connections
- `session.php` - Session configuration
- `mail.php` - Email configuration

Access via `config('file.key')` helper.

## Storage Directory (`storage/`)

```
storage/
├── app/                   # Application generated files
│   ├── private/          # Private files
│   └── public/           # Public files (symlinked to public/storage)
├── framework/            # Framework generated files
│   ├── cache/
│   ├── sessions/
│   └── views/            # Compiled Blade templates
└── logs/                 # Application logs
```

### Storage Notes
- `storage/app/public` should be symlinked to `public/storage`
- Never commit generated files in `storage/framework/`
- Logs written to `storage/logs/laravel.log`

## Public Directory (`public/`)

```
public/
├── index.php             # Application entry point
├── .htaccess            # Apache rewrite rules
└── build/               # Compiled frontend assets (generated)
```

### Public Directory Rules
- Only entry point for web requests
- Static assets served directly
- Never put sensitive files here
- Vite builds to `public/build/`

## Bootstrap Directory (`bootstrap/`)

```
bootstrap/
├── app.php              # Application bootstrap
├── providers.php        # Service provider registration
└── cache/              # Framework bootstrap cache
```

Framework initialization files - rarely need modification.

## File Naming Conventions

- **PHP Classes**: PascalCase matching class name (`UserController.php`)
- **Migrations**: snake_case with timestamp prefix
- **Views**: kebab-case or snake_case (`user-profile.blade.php`)
- **Config files**: kebab-case (`database.php`)
- **Routes**: Group related routes, use resource routing where applicable

## Code Organization Best Practices

### Service-Repository Pattern Implementation

1. **Controllers**: 
   - Keep extremely thin (5-10 lines per method)
   - Only handle HTTP concerns (request/response)
   - Delegate all logic to services
   - Use dependency injection for services
   - Return API resources for consistent responses

2. **Services**:
   - Contain all business logic
   - Orchestrate multiple repositories
   - Handle transactions
   - Throw domain-specific exceptions
   - Return domain objects, not HTTP responses

3. **Repositories**:
   - Only handle data access
   - Implement repository interfaces
   - No business logic
   - Return models or collections
   - Use query builder/Eloquent only

4. **Models**: 
   - Define relationships, scopes, accessors/mutators
   - Keep logic minimal (data-related only)
   - Use traits for shared behavior
   - Define fillable/guarded properties

5. **Migrations**: 
   - One migration per table change
   - Reversible when possible
   - Use descriptive names

6. **Routes**: 
   - Group by resource and version
   - Use route model binding
   - Apply middleware appropriately

7. **Tests**: 
   - Write tests BEFORE implementation (TDD)
   - Mirror application structure
   - Unit tests for services and repositories
   - Feature tests for API endpoints
   - Mock dependencies in unit tests

8. **Service Providers**: 
   - Bind repository interfaces to implementations
   - Register services as singletons when appropriate
   - Keep registration organized

### Dependency Injection Pattern

```php
// Controller example
class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}
    
    public function store(CreateUserRequest $request)
    {
        $user = $this->userService->create($request->validated());
        return new UserResource($user);
    }
}

// Service example
class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TenantService $tenantService
    ) {}
    
    public function create(array $data): User
    {
        // Business logic here
        return $this->userRepository->create($data);
    }
}

// Repository binding in AppServiceProvider
public function register(): void
{
    $this->app->bind(
        UserRepositoryInterface::class,
        EloquentUserRepository::class
    );
}
```

## PSR Standards

- Follow PSR-4 autoloading
- Follow PSR-12 coding style (enforced by Laravel Pint)
- Use type hints and return types (PHP 8.3+)
- Use strict types: `declare(strict_types=1);`
