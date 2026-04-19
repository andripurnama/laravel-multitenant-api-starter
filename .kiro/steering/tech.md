# Technology Stack

## Architecture Pattern
- **Service-Repository Pattern** - Separation of business logic and data access
- **Dependency Injection** - Constructor injection for all dependencies
- **Interface-based Design** - Repository contracts for flexibility and testability

## Development Methodology
- **Test-Driven Development (TDD)** - Write tests before implementation
- **Continuous Integration/Continuous Deployment (CI/CD)** - Automated testing and deployment

## Core Framework
- **Laravel 13** (latest version) - PHP web application framework
- **PHP 8.3+** - Required minimum version

## Frontend
- **Vite 8** - Modern build tool and dev server
- **Tailwind CSS 4** - Utility-first CSS framework
- **Laravel Vite Plugin** - Integration between Laravel and Vite

## Database
- **PostgreSQL** - Primary database (production)
- **SQLite** - Testing database (in-memory)
- **Eloquent ORM** - Database abstraction layer

## Testing
- **Pest 4** - Modern PHP testing framework (preferred over PHPUnit)
- **Pest Laravel Plugin** - Laravel-specific testing helpers
- **Faker** - Test data generation
- **Mockery** - Mocking framework

## Development Tools
- **Laravel Pint** - Opinionated PHP code style fixer
- **Laravel Pail** - Real-time log viewer
- **Laravel Tinker** - REPL for Laravel
- **Concurrently** - Run multiple dev processes simultaneously

## Key Dependencies
- **Collision** - Beautiful error reporting for CLI
- **Laravel Framework** - Core framework package

## Common Commands

### Setup
```bash
composer run setup              # Full project setup (install, env, key, migrate, npm)
```

### Development
```bash
composer run dev                # Start all dev services (server, queue, logs, vite)
php artisan serve               # Start development server only
npm run dev                     # Start Vite dev server only
php artisan queue:listen        # Start queue worker
php artisan pail                # View real-time logs
```

### Testing
```bash
composer run test               # Run all tests with Pest
php artisan test                # Alternative test command
php artisan test --filter=TestName  # Run specific test
php artisan test --coverage     # Run tests with coverage report
php artisan test --parallel     # Run tests in parallel
./vendor/bin/pest               # Run Pest directly
./vendor/bin/pest --coverage    # Coverage with Pest
./vendor/bin/pest --filter="user service"  # Filter tests
```

### TDD Workflow Commands
```bash
# Watch mode for TDD (requires pest-plugin-watch)
./vendor/bin/pest --watch

# Run specific test file
php artisan test tests/Unit/Services/UserServiceTest.php

# Run tests by group
php artisan test --group=services
php artisan test --group=repositories
```

### Code Quality
```bash
./vendor/bin/pint               # Format code with Laravel Pint
./vendor/bin/pint --test        # Check code style without fixing
```

### Database
```bash
php artisan migrate             # Run migrations
php artisan migrate:fresh       # Drop all tables and re-run migrations
php artisan migrate:fresh --seed # Fresh migration with seeders
php artisan db:seed             # Run database seeders
```

### Build
```bash
npm run build                   # Build frontend assets for production
```

### Artisan
```bash
php artisan list                # List all available commands
php artisan make:model ModelName -m  # Create model with migration
php artisan make:controller ControllerName  # Create controller
php artisan make:migration create_table_name  # Create migration
php artisan route:list          # List all registered routes
php artisan config:clear        # Clear configuration cache
php artisan cache:clear         # Clear application cache
```

## Configuration Files
- `composer.json` - PHP dependencies and scripts
- `package.json` - Node dependencies and build scripts
- `phpunit.xml` - Test configuration
- `vite.config.js` - Frontend build configuration
- `.env` - Environment variables (not committed)
- `.env.example` - Environment template

## Autoloading
- `App\` namespace maps to `app/` directory
- `Database\Factories\` maps to `database/factories/`
- `Database\Seeders\` maps to `database/seeders/`
- `Tests\` maps to `tests/` directory

## CI/CD Pipeline

### Continuous Integration
Automated testing and quality checks on every commit/PR:

1. **Code Quality Checks**
   - Run Laravel Pint for code style
   - Static analysis (optional: PHPStan/Larastan)
   - Check for security vulnerabilities

2. **Automated Testing**
   - Run full test suite with Pest
   - Generate coverage reports
   - Fail build if tests fail or coverage drops

3. **Database Testing**
   - Run migrations in CI environment
   - Test with SQLite in-memory database
   - Validate migration rollbacks

### Continuous Deployment
Automated deployment after successful CI:

1. **Staging Deployment**
   - Deploy to staging on merge to develop/main
   - Run smoke tests
   - Database migrations (with backup)

2. **Production Deployment**
   - Deploy on tagged releases
   - Zero-downtime deployment
   - Automated rollback on failure

### Recommended CI/CD Tools
- **GitHub Actions** - Native GitHub integration
- **GitLab CI/CD** - Built-in GitLab pipelines
- **CircleCI** - Fast, configurable pipelines
- **Jenkins** - Self-hosted option

### Example GitHub Actions Workflow
```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: composer run test
      - name: Code Style
        run: ./vendor/bin/pint --test
```

### Pre-commit Hooks (Optional)
```bash
# Run tests before commit
php artisan test

# Run code style fixer
./vendor/bin/pint

# Can be automated with git hooks or tools like Husky
```
