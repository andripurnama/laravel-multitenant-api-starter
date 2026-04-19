# Design Patterns & Best Practices

## Service-Repository Pattern

### Architecture Overview

```
Controller → Service → Repository → Model → Database
     ↓          ↓           ↓
  Request   Business    Data Access
  Response   Logic       Layer
```

### Layer Responsibilities

#### Controllers (`app/Http/Controllers/`)
- **Purpose**: Handle HTTP requests and responses only
- **Responsibilities**:
  - Validate requests (via Form Requests)
  - Call service methods
  - Return API resources
  - Handle HTTP status codes
- **Rules**:
  - Keep methods under 10 lines
  - No business logic
  - No direct database access
  - Inject services via constructor

```php
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        
        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
```

#### Services (`app/Services/`)
- **Purpose**: Contain all business logic
- **Responsibilities**:
  - Orchestrate multiple repositories
  - Implement business rules
  - Handle transactions
  - Throw domain exceptions
  - Return domain objects
- **Rules**:
  - No HTTP concerns (no Request/Response objects)
  - Inject repository interfaces
  - Use database transactions for multi-step operations
  - Type-hint all parameters and returns

```php
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly EventDispatcher $events
    ) {}

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $tenant = $this->tenantRepository->findOrFail($data['tenant_id']);
            
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'tenant_id' => $tenant->id,
            ]);
            
            $this->events->dispatch(new UserCreated($user));
            
            return $user;
        });
    }
}
```

#### Repositories (`app/Repositories/`)
- **Purpose**: Abstract data access layer
- **Responsibilities**:
  - CRUD operations
  - Query building
  - Data retrieval
  - Return models/collections
- **Rules**:
  - No business logic
  - Implement repository interfaces
  - Use Eloquent or Query Builder only
  - Keep methods focused and simple

```php
// Interface
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function findByEmail(string $email): ?User;
}

// Implementation
class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
```

#### Repository Registration
Bind interfaces to implementations in `AppServiceProvider`:

```php
public function register(): void
{
    $this->app->bind(
        UserRepositoryInterface::class,
        EloquentUserRepository::class
    );
    
    $this->app->bind(
        TenantRepositoryInterface::class,
        EloquentTenantRepository::class
    );
}
```

### Actions Pattern (Alternative)

For single-purpose operations, use Action classes:

```php
// app/Actions/CreateUserAction.php
class CreateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(array $data): User
    {
        // Single focused operation
        return $this->userRepository->create($data);
    }
}
```

## Test-Driven Development (TDD)

### TDD Cycle: Red-Green-Refactor

1. **Red**: Write a failing test
2. **Green**: Write minimal code to pass
3. **Refactor**: Improve code while keeping tests green

### Testing Layers

#### Unit Tests (`tests/Unit/`)
Test individual classes in isolation with mocked dependencies.

```php
// tests/Unit/Services/UserServiceTest.php
use App\Services\UserService;
use App\Repositories\Contracts\UserRepositoryInterface;

test('creates user with valid data', function () {
    // Arrange
    $repository = Mockery::mock(UserRepositoryInterface::class);
    $repository->shouldReceive('create')
        ->once()
        ->with(['name' => 'John', 'email' => 'john@test.com'])
        ->andReturn(new User(['id' => 1, 'name' => 'John']));
    
    $service = new UserService($repository);
    
    // Act
    $user = $service->createUser([
        'name' => 'John',
        'email' => 'john@test.com'
    ]);
    
    // Assert
    expect($user->name)->toBe('John');
});
```

#### Repository Tests (`tests/Unit/Repositories/`)
Test data access with real database (SQLite in-memory).

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('repository creates user in database', function () {
    $repository = new EloquentUserRepository();
    
    $user = $repository->create([
        'name' => 'John',
        'email' => 'john@test.com',
        'password' => bcrypt('password'),
    ]);
    
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John');
    
    $this->assertDatabaseHas('users', [
        'email' => 'john@test.com'
    ]);
});
```

#### Feature Tests (`tests/Feature/`)
Test complete API endpoints with real HTTP requests.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can be created via api endpoint', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@test.com',
        'password' => 'password123',
        'tenant_id' => 1,
    ];
    
    $response = $this->postJson('/api/users', $data);
    
    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'created_at']
        ]);
    
    $this->assertDatabaseHas('users', [
        'email' => 'john@test.com'
    ]);
});
```

### Test Organization

```
tests/
├── Feature/
│   ├── Api/
│   │   ├── UserApiTest.php
│   │   └── TenantApiTest.php
│   └── Auth/
│       └── LoginTest.php
├── Unit/
│   ├── Services/
│   │   ├── UserServiceTest.php
│   │   └── TenantServiceTest.php
│   ├── Repositories/
│   │   ├── UserRepositoryTest.php
│   │   └── TenantRepositoryTest.php
│   └── Actions/
│       └── CreateUserActionTest.php
└── Pest.php
```

### Testing Best Practices

1. **Arrange-Act-Assert Pattern**
   - Arrange: Set up test data and mocks
   - Act: Execute the code under test
   - Assert: Verify the outcome

2. **Test Naming**
   - Use descriptive names: `test('user service creates user with valid data')`
   - Explain behavior, not implementation

3. **One Assertion Per Test** (when possible)
   - Focus on single behavior
   - Makes failures easier to diagnose

4. **Use Factories**
   - Generate test data with factories
   - Keep tests DRY and maintainable

5. **Mock External Dependencies**
   - Mock repositories in service tests
   - Mock services in controller tests
   - Use real database for repository tests

6. **Test Edge Cases**
   - Happy path
   - Validation failures
   - Not found scenarios
   - Permission denied
   - Duplicate entries

## Dependency Injection

### Constructor Injection (Preferred)

```php
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly MailService $mailService
    ) {}
}
```

### Method Injection (For Specific Methods)

```php
public function handle(Request $request, UserService $userService)
{
    // $request and $userService are automatically injected
}
```

## Exception Handling

### Custom Exceptions

```php
// app/Exceptions/UserNotFoundException.php
class UserNotFoundException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("User with ID {$userId} not found");
    }
}

// Usage in service
public function findUser(int $id): User
{
    $user = $this->userRepository->find($id);
    
    if (!$user) {
        throw new UserNotFoundException($id);
    }
    
    return $user;
}
```

## API Resources

Transform models to consistent JSON responses:

```php
// app/Http/Resources/UserResource.php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

## Form Requests

Validate incoming data:

```php
// app/Http/Requests/CreateUserRequest.php
class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or implement authorization logic
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'tenant_id' => ['required', 'exists:tenants,id'],
        ];
    }
}
```

## Code Quality Checklist

- [ ] Controllers are thin (< 10 lines per method)
- [ ] Business logic is in services
- [ ] Data access is in repositories
- [ ] Repository interfaces are defined
- [ ] Dependencies are injected via constructor
- [ ] All methods have type hints
- [ ] Tests are written before implementation (TDD)
- [ ] Unit tests mock dependencies
- [ ] Feature tests use real database
- [ ] Code follows PSR-12 (run Pint)
- [ ] No direct Eloquent calls in controllers
- [ ] API responses use Resource classes
- [ ] Validation uses Form Requests
- [ ] Exceptions are meaningful and specific
