---
inclusion: always
---

# Project Structure

## Directory Organization

### Application Code
- `app/` - Core application code
  - `Http/Controllers/` - HTTP controllers
  - `Models/` - Eloquent models
  - `Providers/` - Service providers

### Configuration
- `config/` - Application configuration files
- `.env` - Environment variables (not committed)
- `.env.example` - Example environment configuration

### Frontend Assets
- `resources/` - Frontend source files
  - `css/` - Stylesheets (Tailwind CSS)
  - `js/` - JavaScript files
  - `views/` - Blade templates
- `public/` - Publicly accessible files (compiled assets, images)

### Routing
- `routes/` - Route definitions
  - `web.php` - Web routes
  - `console.php` - Console commands

### Database
- `database/` - Database related files
  - `migrations/` - Database migrations
  - `seeders/` - Database seeders
  - `factories/` - Model factories for testing
  - `database.sqlite` - SQLite database file

### Testing
- `tests/` - Test files
  - `Feature/` - Feature tests
  - `Unit/` - Unit tests

### Bootstrap & Storage
- `bootstrap/` - Framework bootstrap files
  - `app.php` - Application bootstrap configuration
  - `cache/` - Framework cache files
- `storage/` - Application storage
  - `app/` - Application files
  - `framework/` - Framework generated files
  - `logs/` - Application logs

### Development Environment
- `.ddev/` - DDEV configuration and customizations
- `vendor/` - Composer dependencies (not committed)
- `node_modules/` - NPM dependencies (not committed)

## Key Files

- `artisan` - Laravel command-line interface
- `composer.json` - PHP dependencies and scripts
- `package.json` - Node.js dependencies and scripts
- `vite.config.js` - Vite configuration
- `phpunit.xml` - PHPUnit configuration
- `.editorconfig` - Editor configuration for consistent coding style

## Architectural Best Practices

### Embrace the Default Structure

Laravel's default structure is designed for clarity and separation of concerns. Resist the urge to drastically alter it unless there's a strong, well-justified reason.

**Standard Laravel directories:**
- `app/` - Core application logic (Models, Controllers, Services, etc.)
- `bootstrap/` - Application bootstrapping
- `config/` - Configuration files
- `database/` - Migrations, seeders, factories
- `public/` - Publicly accessible assets (CSS, JS, images, index.php)
- `resources/` - Views, language files, raw assets
- `routes/` - Route definitions
- `storage/` - Compiled views, file uploads, logs
- `tests/` - Application tests
- `vendor/` - Composer dependencies

### Organize app/ by Feature or Domain (for larger applications)

While the default `app/Http/Controllers`, `app/Models` structure works for smaller projects, larger applications benefit from grouping related components within `app/` by feature or domain:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── LoginController.php
│   │   └── ProductController.php
│   └── Middleware/
├── Models/
│   ├── User.php
│   └── Product.php
├── Services/
│   └── ProductService.php
└── Repositories/
    └── ProductRepository.php
```

### "Fat Models, Skinny Controllers"

Keep controllers lean, primarily handling request input and delegating business logic to models or dedicated service classes.

**Bad - Controller doing too much:**
```php
class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([...]);
        $order = Order::create($validated);
        Stripe::charge($order->total);
        Mail::to($order->user)->send(new OrderConfirmation($order));
        // ... more business logic
        return response()->json($order);
    }
}
```

**Good - Delegating to service layer:**
```php
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([...]);
        $order = $this->orderService->createOrder($validated);
        return response()->json($order, 201);
    }
}
```

### Use Service Classes for Complex Business Logic

Extract complex business logic that doesn't directly belong in a model into dedicated service classes, typically placed in `app/Services/`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

class OrderService
{
    public function __construct(
        private PaymentService $paymentService,
        private NotificationService $notificationService,
        private InventoryService $inventoryService
    ) {}
    
    public function createOrder(array $data): Order
    {
        $order = Order::create($data);
        
        $this->paymentService->processPayment($order);
        $this->notificationService->sendOrderConfirmation($order);
        $this->inventoryService->updateStock($order);
        
        return $order;
    }
}
```

### Utilize Artisan Commands

Always use `ddev artisan make:` commands to generate boilerplate code, ensuring consistency and adherence to the intended structure:

```bash
ddev artisan make:model Product -mfc     # Model with migration, factory, controller
ddev artisan make:controller UserController --resource
ddev artisan make:migration create_products_table
ddev artisan make:seeder ProductSeeder
ddev artisan make:request StoreProductRequest
ddev artisan make:service ProductService  # If custom command exists
```

### Manage Configuration with .env and config/

- Store sensitive information in `.env` (excluded from version control)
- Access environment variables via `env()` or `config()` helpers
- Define general application settings in `config/` files
- Never use `env()` directly in application code - always use `config()`

**Bad:**
```php
$apiKey = env('STRIPE_API_KEY');  // Don't do this in app code
```

**Good:**
```php
// In config/services.php
'stripe' => [
    'key' => env('STRIPE_API_KEY'),
],

// In application code
$apiKey = config('services.stripe.key');
```

## Naming Conventions

### PHP/Laravel
- Controllers: PascalCase with `Controller` suffix (e.g., `UserController`)
- Models: Singular PascalCase (e.g., `User`)
- Database tables: Plural snake_case (e.g., `users`)
- Migrations: Snake_case with timestamp prefix
- Routes: Kebab-case for URLs (e.g., `/user-profile`)
- Methods: camelCase
- Variables: snake_case (per laravel.md)
- Files: kebab-case (e.g., `user-service.php`)

### Frontend
- CSS files: Kebab-case (e.g., `app.css`)
- JavaScript files: Kebab-case (e.g., `app.js`)
- Blade views: Kebab-case (e.g., `welcome.blade.php`)

## Code Style

Follow Laravel conventions and PSR-12 standards. Use Laravel Pint for automatic code formatting:
- 4 spaces for indentation (PHP, JS)
- 2 spaces for YAML files
- LF line endings
- UTF-8 encoding
- Trim trailing whitespace
- Insert final newline
- Run `ddev exec ./vendor/bin/pint` before committing
