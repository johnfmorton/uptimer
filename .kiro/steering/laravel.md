---
inclusion: always
---

# Laravel Development Best Practices

## File Naming Conventions

### Files
Use kebab-case for file names:
```
my-class-file.php
user-service.php
order-repository.php
```

### Classes and Enums
Use PascalCase:
```php
class UserService {}
class OrderRepository {}
enum UserRole {}
enum OrderStatus {}
```

### Methods
Use camelCase:
```php
public function validateEmail() {}
public function processPayment() {}
public function getUserById() {}
```

### Variables and Properties
Use snake_case:
```php
$user_id = 1;
$order_total = 100.50;
protected $created_at;
```

### Constants and Enum Cases
Use SCREAMING_SNAKE_CASE:
```php
const MAX_LOGIN_ATTEMPTS = 5;
const API_VERSION = '1.0';

enum UserRole: string {
    case ADMIN_ROLE = 'admin';
    case USER_ROLE = 'user';
}
```

## Code Quality Standards

### Styling
- Use Laravel Pint for consistent code formatting
- Follow PSR-12 coding standards
- Run `ddev exec ./vendor/bin/pint` before committing

### Type Safety
Always use strict typing where possible:
```php
<?php

declare(strict_types=1);

namespace App\Services;

class UserService
{
    public function createUser(string $name, string $email): User
    {
        // Implementation
    }
    
    public function getUserAge(User $user): int
    {
        // Implementation
    }
}
```

### Documentation
Provide comprehensive PHPDoc blocks for better DX and IDE support:
```php
/**
 * Calculate the total price of items in a shopping cart.
 *
 * @param  array<int, array{price: float, quantity: int}>  $items
 * @return float The total calculated price
 * @throws \InvalidArgumentException If items array is invalid
 */
public function calculateTotalPrice(array $items): float
{
    // Implementation
}
```

## Code Comments Best Practices

### Prioritize Self-Documenting Code
Use clear, descriptive names that eliminate the need for obvious comments:

```php
// Bad: Comment explains what is already obvious
$userAge = calculateAge($birthDate); // Calculate user age

// Good: Self-explanatory code
$age = calculateAge($birthDate);
```

### Explain Why, Not What
Comments should clarify intent, design choices, or workarounds:

```php
// Why: Workaround for known bug in payment gateway API where pending 
// status is not correctly returned for transactions under $10
if ($amount < 10 && $status === 'processing') {
    $status = 'pending';
}

// Why: Cache user permissions for 1 hour to reduce database load
// during high-traffic periods (per performance audit 2024-11)
Cache::remember("user.{$user_id}.permissions", 3600, function () {
    return $this->loadPermissions();
});
```

### Document Complex Algorithms or Business Logic
Explain sophisticated algorithms or intricate business rules:

```php
/**
 * Calculate shipping cost using tiered pricing model.
 * 
 * Algorithm:
 * 1. Base rate determined by weight brackets
 * 2. Distance multiplier applied for zones > 500km
 * 3. Volume surcharge added if package exceeds dimensional weight
 * 4. Express shipping doubles the final cost
 */
public function calculateShippingCost(Package $package, string $destination): float
{
    // Step 1: Determine base rate from weight
    $base_rate = $this->getBaseRateByWeight($package->weight);
    
    // Step 2: Apply distance multiplier
    $distance = $this->calculateDistance($destination);
    if ($distance > 500) {
        $base_rate *= 1.5;
    }
    
    // Step 3: Check dimensional weight
    $dimensional_weight = $this->calculateDimensionalWeight($package);
    if ($dimensional_weight > $package->weight) {
        $base_rate += 10.00; // Volume surcharge
    }
    
    // Step 4: Apply express shipping multiplier
    if ($package->is_express) {
        $base_rate *= 2;
    }
    
    return $base_rate;
}
```

### Provide Context for External Resources
Reference external documentation, APIs, or copied code:

```php
// Reference: Stripe API documentation for idempotency keys
// https://stripe.com/docs/api/idempotent_requests
$idempotency_key = Str::uuid();

// Implementation based on RFC 3986 Section 2.1
// https://tools.ietf.org/html/rfc3986#section-2.1
$encoded_url = $this->percentEncode($url);
```

### Mark Incomplete Implementations
Use TODO and FIXME comments for future work:

```php
// TODO: Implement proper retry logic with exponential backoff
// TODO: Add rate limiting to prevent API abuse
public function sendNotification(User $user, string $message): void
{
    // Current basic implementation
    Mail::to($user->email)->send(new Notification($message));
}

// FIXME: This query causes N+1 problem with large datasets
// FIXME: Need to eager load relationships
$users = User::all();
foreach ($users as $user) {
    echo $user->profile->bio;
}
```

### Avoid Redundant Comments
Do not comment on self-evident code:

```php
// Bad: Redundant comment
$total = 0; // Initialize total to zero
foreach ($items as $item) { // Loop through items
    $total += $item->price; // Add price to total
}

// Good: Clear code without unnecessary comments
$total = 0;
foreach ($items as $item) {
    $total += $item->price;
}
```

### Keep Comments Up-to-Date
Outdated comments are worse than no comments. Update them when code changes:

```php
// Bad: Outdated comment
// Returns user's full name
public function getUserDisplayName(User $user): string
{
    // Now returns email if name is not set
    return $user->name ?? $user->email;
}

// Good: Accurate comment
// Returns user's name, falling back to email if name is not set
public function getUserDisplayName(User $user): string
{
    return $user->name ?? $user->email;
}
```

## Architecture Patterns

### Service Layer Pattern
Encapsulate business logic in dedicated service classes:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    /**
     * Create a new user with validated data.
     *
     * @param  array{name: string, email: string, password: string}  $data
     * @return User The newly created user
     * @throws \InvalidArgumentException If email already exists
     */
    public function createUser(array $data): User
    {
        // Validate email uniqueness
        if ($this->userRepository->existsByEmail($data['email'])) {
            throw new \InvalidArgumentException('Email already exists');
        }
        
        // Hash password before storage
        $data['password'] = Hash::make($data['password']);
        
        // Delegate persistence to repository
        return $this->userRepository->create($data);
    }
}
```

### Repository Pattern
Abstract data access logic:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Find user by email address.
     *
     * @param  string  $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
    
    /**
     * Check if user exists with given email.
     *
     * @param  string  $email
     * @return bool
     */
    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }
    
    /**
     * Create a new user record.
     *
     * @param  array<string, mixed>  $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create($data);
    }
}
```

### Dependency Injection
Use constructor injection for better testability:

```php
<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}
    
    /**
     * Store a newly created user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);
        
        // Delegate business logic to service layer
        $user = $this->userService->createUser($validated);
        
        return response()->json($user, 201);
    }
}
```

### Single Responsibility Principle
Each class should have one clear purpose:

```php
// Bad: Controller doing too much
class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([...]);
        
        // Business logic
        $order = Order::create($validated);
        
        // Payment processing
        Stripe::charge($order->total);
        
        // Email notification
        Mail::to($order->user)->send(new OrderConfirmation($order));
        
        // Inventory update
        foreach ($order->items as $item) {
            $item->product->decrement('stock', $item->quantity);
        }
        
        return response()->json($order);
    }
}

// Good: Separated concerns
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([...]);
        
        // Delegate to service layer
        $order = $this->orderService->createOrder($validated);
        
        return response()->json($order, 201);
    }
}

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
        
        // Each service handles its own responsibility
        $this->paymentService->processPayment($order);
        $this->notificationService->sendOrderConfirmation($order);
        $this->inventoryService->updateStock($order);
        
        return $order;
    }
}
```

## Blade Templating Best Practices

### Component Syntax
Use Blade's component syntax (`<x-component />`) for reusable UI elements:

```blade
{{-- Anonymous component (resources/views/components/alert.blade.php) --}}
<x-alert type="error" :message="$message" />

{{-- Class-based component --}}
<x-inputs.button type="submit">Save</x-inputs.button>

{{-- Component with slots --}}
<x-card>
    <x-slot:header>
        Card Title
    </x-slot:header>
    
    Card content goes here
    
    <x-slot:footer>
        <button>Action</button>
    </x-slot:footer>
</x-card>
```

### Layout Inheritance
Use `@extends` and `@section` for layout inheritance:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
    <head>
        <title>@yield('title', 'Default Title')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <x-header />
        
        @isset($header)
            <header>{{ $header }}</header>
        @endisset
        
        <main>
            @yield('content')
        </main>
        
        <x-footer />
        
        @stack('scripts')
    </body>
</html>

{{-- resources/views/pages/home.blade.php --}}
@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
    <h1>Welcome</h1>
@endsection

@push('scripts')
    <script src="/js/home.js"></script>
@endpush
```

### Include vs Components
Use `@include` for simple partials, components for reusable UI with logic:

```blade
{{-- Simple include (no logic) --}}
@include('components.favicon')
@include('partials.sidebar', ['user' => $user])

{{-- Component (with logic, attributes, slots) --}}
<x-alert type="success" />
<x-user-card :user="$user" />
```

### Conditional Rendering
Use Blade directives for clean conditionals:

```blade
@auth
    <p>Welcome, {{ auth()->user()->name }}</p>
@endauth

@guest
    <a href="/login">Login</a>
@endguest

@if($user->isAdmin())
    <x-admin-panel />
@elseif($user->isModerator())
    <x-moderator-panel />
@else
    <x-user-panel />
@endif

@unless($user->isSubscribed())
    <x-subscription-banner />
@endunless

@empty($posts)
    <p>No posts found</p>
@endempty
```

### Loops and Iteration
Use `@foreach`, `@forelse` for clean iteration:

```blade
@forelse($users as $user)
    <x-user-card :user="$user" />
@empty
    <p>No users found</p>
@endforelse

@foreach($items as $item)
    <div class="{{ $loop->first ? 'first' : '' }} {{ $loop->last ? 'last' : '' }}">
        {{ $item->name }}
    </div>
@endforeach
```

### Data Escaping
Always escape output unless you explicitly trust the content:

```blade
{{-- Escaped (safe) --}}
{{ $user->name }}
{{ $user->bio }}

{{-- Unescaped (dangerous - only for trusted content) --}}
{!! $trustedHtml !!}

{{-- Raw output in attributes --}}
<div data-user="{{ $user->toJson() }}"></div>
```

### Component Attributes
Use attribute bags for flexible component APIs:

```blade
{{-- resources/views/components/button.blade.php --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn']) }}>
    {{ $slot }}
</button>

{{-- Usage --}}
<x-button type="submit" class="btn-primary">Save</x-button>
{{-- Renders: <button type="submit" class="btn btn-primary">Save</button> --}}
```

### Vite Asset Loading
Use `@vite` directive for asset bundling:

```blade
{{-- Load CSS and JS --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Load specific page assets --}}
@vite(['resources/js/pages/dashboard.js'])
```

### CSRF Protection
Always include CSRF token in forms:

```blade
<form method="POST" action="/users">
    @csrf
    {{-- or --}}
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
</form>

{{-- Method spoofing for PUT/PATCH/DELETE --}}
<form method="POST" action="/users/1">
    @csrf
    @method('PUT')
</form>
```

### Error Handling
Display validation errors cleanly:

```blade
@error('email')
    <span class="error">{{ $message }}</span>
@enderror

{{-- All errors --}}
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

## Laravel Package Development Best Practices

When developing reusable components:

1. **Use service providers** for bootstrapping
2. **Publish configuration files** for customization
3. **Provide facades** for convenient access
4. **Include comprehensive tests**
5. **Document all public APIs**
6. **Follow semantic versioning**

## Developer Experience Focus

### Excellent Autocompletion
Provide type hints and return types for IDE support:

```php
/**
 * @param  Collection<int, User>  $users
 * @return Collection<int, string>
 */
public function extractEmails(Collection $users): Collection
{
    return $users->pluck('email');
}
```

### Fluent Interfaces
Design chainable methods for better readability:

```php
$query = User::query()
    ->active()
    ->verified()
    ->whereHas('orders', fn($q) => $q->recent())
    ->with(['profile', 'preferences'])
    ->get();
```

### Clear Error Messages
Provide helpful context in exceptions:

```php
throw new \InvalidArgumentException(
    "Invalid payment method '{$method}'. Supported methods: " . 
    implode(', ', $this->supportedMethods)
);
```
