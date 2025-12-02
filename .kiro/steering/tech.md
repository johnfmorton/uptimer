---
inclusion: always
---

# Technology Stack

## Core Framework
- Laravel 12 (PHP 8.2+)
- PHP 8.4 (via DDEV)

## Frontend
- Vite 7 for asset bundling
- Tailwind CSS 4
- Axios for HTTP requests

## Database
- SQLite (default)
- MySQL 8.4 available via DDEV
- Eloquent ORM for database interactions

## Development Environment
- DDEV for local development (nginx-fpm, MySQL 8.4)
- Composer 2 for PHP dependency management
- Node.js for frontend tooling

## Testing
- PHPUnit 11.5+ for unit and feature tests
- Laravel Pint for code style enforcement

## Key Dependencies
- Laravel Tinker for REPL
- Laravel Pail for log viewing
- Faker for test data generation
- Mockery for mocking in tests

## ⚠️ IMPORTANT: This Project Uses DDEV

**ALL commands must be prefixed with `ddev`. Never use `php`, `composer`, or `npm` directly.**

## Common Commands

### Setup
```bash
ddev composer run setup
```
Installs dependencies, generates app key, runs migrations, and builds assets.

### Development
```bash
ddev composer run dev
```
Starts Laravel server, queue worker, log viewer, and Vite dev server concurrently.

Or run services individually:
```bash
ddev artisan serve          # Start development server (NOT php artisan serve)
ddev npm run dev            # Start Vite dev server (NOT npm run dev)
ddev artisan queue:listen   # Start queue worker
ddev artisan pail           # View logs
```

### Testing
```bash
ddev composer run test
# or
ddev artisan test
```

### Code Style
```bash
ddev exec ./vendor/bin/pint
```

### Database
```bash
ddev artisan migrate              # Run migrations
ddev artisan migrate:fresh        # Drop all tables and re-run migrations
ddev artisan db:seed              # Run database seeders
ddev artisan migrate:fresh --seed # Fresh migration with seeding
```

### DDEV Environment
```bash
ddev start          # Start DDEV environment
ddev stop           # Stop DDEV environment
ddev restart        # Restart DDEV environment
ddev ssh            # SSH into web container
ddev describe       # Show project details
ddev snapshot       # Create database snapshot
```

### Cache Management
```bash
ddev artisan config:cache   # Cache configuration
ddev artisan config:clear   # Clear configuration cache
ddev artisan route:cache    # Cache routes
ddev artisan route:clear    # Clear route cache
ddev artisan view:cache     # Cache views
ddev artisan view:clear     # Clear view cache
ddev artisan cache:clear    # Clear application cache
```
