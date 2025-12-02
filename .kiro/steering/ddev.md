---
inclusion: always
---

# DDEV Local Development Environment

## 🚨 MANDATORY: This is a DDEV Project

**Every command in this project MUST use the `ddev` prefix. There are NO exceptions.**

When suggesting commands to the user, you MUST:
1. Always prefix with `ddev` (e.g., `ddev artisan`, `ddev composer`, `ddev npm`)
2. Never suggest `php artisan serve` - use `ddev artisan serve` or `ddev composer dev`
3. Never suggest `npm install` or `npm run` - use `ddev npm install` or `ddev npm run`
4. Never suggest `composer install` - use `ddev composer install`

If you catch yourself about to suggest a command without `ddev`, STOP and add the `ddev` prefix.

## Environment Configuration

This project uses DDEV for local development with the following configuration:

- Project Type: Laravel
- PHP Version: 8.4
- Web Server: nginx-fpm
- Database: MySQL 8.4
- Composer: Version 2
- Project URL: https://kiro-laravel-ddev-skeleton-template.ddev.site

## ⚠️ CRITICAL: Command Execution Rules

**THIS PROJECT USES DDEV. ALL COMMANDS MUST BE EXECUTED INSIDE THE DDEV CONTAINER.**

**NEVER suggest or use these commands:**
- ❌ `php artisan ...` (use `ddev artisan ...` instead)
- ❌ `composer ...` (use `ddev composer ...` instead)
- ❌ `npm ...` (use `ddev npm ...` instead)
- ❌ `php ...` (use `ddev exec php ...` instead)
- ❌ `./vendor/bin/...` (use `ddev exec ./vendor/bin/...` instead)

**ALWAYS prefix commands with `ddev`:**
- ✅ `ddev artisan ...`
- ✅ `ddev composer ...`
- ✅ `ddev npm ...`
- ✅ `ddev exec ...`

### Artisan Commands
Always use `ddev artisan` instead of `php artisan`:
```bash
ddev artisan migrate
ddev artisan make:controller UserController
ddev artisan tinker
ddev artisan queue:work
ddev artisan pail
ddev artisan test
```

### Composer Commands
Always use `ddev composer` instead of `composer`:
```bash
ddev composer install
ddev composer require package/name
ddev composer update
ddev composer dump-autoload
ddev composer dev  # Run development environment
```

### NPM Commands
Always use `ddev npm` instead of `npm`:
```bash
ddev npm install
ddev npm run dev
ddev npm run build
```

### Database Access
Use `ddev mysql` for direct database access:
```bash
ddev mysql                    # Connect to MySQL CLI
ddev export-db --file=dump.sql  # Export database
ddev import-db --file=dump.sql  # Import database
ddev snapshot                 # Create database snapshot
ddev snapshot restore         # Restore database snapshot
```

## Development Workflow

### Starting Development
```bash
ddev start                    # Start DDEV environment
ddev composer dev             # Run full dev environment (server, queue, logs, vite)
```

The `ddev composer dev` command runs concurrently:
- Laravel development server
- Queue worker (database driver)
- Laravel Pail (real-time logs)
- Vite dev server (port 3000)

### Individual Services
Run services separately if needed:
```bash
ddev artisan serve            # Laravel development server
ddev npm run dev              # Vite dev server only
ddev artisan queue:work --tries=1  # Queue worker
ddev artisan pail --timeout=0      # Real-time log monitoring
```

### Stopping Development
```bash
ddev stop                     # Stop DDEV environment
ddev restart                  # Restart DDEV environment
```

## Queue Management

The project uses database-driven queues (configured in `.env`):

```bash
ddev artisan queue:work --tries=1  # Start queue worker
ddev artisan queue:failed          # List failed jobs
ddev artisan queue:retry all       # Retry all failed jobs
ddev artisan queue:flush           # Clear all failed jobs
```

## Debugging Tools

### Laravel Pail
Real-time log streaming:
```bash
ddev artisan pail
ddev artisan pail --timeout=0  # No timeout
```

### Tinker
Interactive REPL for debugging:
```bash
ddev artisan tinker
```

### Container Logs
View DDEV container logs:
```bash
ddev logs                     # View all logs
ddev logs -f                  # Follow logs
ddev logs -s web              # Web container logs only
ddev logs -s db               # Database container logs only
```

## Container Access

### SSH into Containers
```bash
ddev ssh                      # SSH into web container
ddev ssh -s db                # SSH into database container
```

### Execute Commands
```bash
ddev exec <command>           # Execute command in web container
ddev exec -s db <command>     # Execute command in database container
```

## Testing

Always run tests through DDEV:
```bash
ddev composer test            # Run test suite
ddev artisan test             # Run PHPUnit tests
ddev artisan test --filter=UserTest  # Run specific test
```

## Database Migrations

Run all database operations through DDEV:
```bash
ddev artisan migrate
ddev artisan migrate:fresh
ddev artisan migrate:fresh --seed
ddev artisan migrate:rollback
ddev artisan db:seed
```

## Cache Management

Clear and cache operations:
```bash
ddev artisan config:clear
ddev artisan config:cache
ddev artisan route:clear
ddev artisan route:cache
ddev artisan view:clear
ddev artisan view:cache
ddev artisan cache:clear
ddev artisan optimize         # Cache config, routes, and views
ddev artisan optimize:clear   # Clear all caches
```

## Code Quality

Run code style checks through DDEV:
```bash
ddev exec ./vendor/bin/pint           # Format code
ddev exec ./vendor/bin/pint --test    # Check code style
```

## Important Notes

1. **Never run PHP, Composer, or NPM commands directly** - always prefix with `ddev`
2. **Database connections** are handled automatically by DDEV - use the environment variables in `.env`
3. **File permissions** are managed by DDEV - no need to manually adjust permissions
4. **Port conflicts** are avoided by DDEV's router - access via the project URL
5. **Environment variables** from `.env` are automatically available in the container

## Troubleshooting

```bash
ddev describe                 # Show project details
ddev poweroff                 # Stop all DDEV projects
ddev debug test               # Test DDEV functionality
ddev debug router             # Debug router issues
ddev restart                  # Restart if issues occur
```
