# Design Document

## Overview

This design implements a controlled registration system for the Laravel application that disables public user registration by default while providing administrative tools for user creation. The solution uses environment-based configuration to control registration availability and introduces an Artisan command for creating users via CLI.

The design follows Laravel's existing authentication patterns and integrates seamlessly with the current Breeze-based authentication system. It maintains backward compatibility with existing authentication flows (login, password reset, profile management) while adding granular control over new user registration.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     HTTP Layer                               │
│  ┌──────────────────────┐      ┌─────────────────────────┐ │
│  │ Registration Routes  │      │  Other Auth Routes      │ │
│  │  - GET /register     │      │  - Login                │ │
│  │  - POST /register    │      │  - Password Reset       │ │
│  └──────────┬───────────┘      │  - Profile              │ │
│             │                  └─────────────────────────┘ │
└─────────────┼──────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Middleware Layer                           │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  RegistrationEnabled Middleware                       │  │
│  │  - Checks config('auth.allow_public_registration')   │  │
│  │  - Returns 403 if disabled                           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────┬───────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────┐
│                  Controller Layer                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  RegisteredUserController                             │  │
│  │  - create(): Display registration form               │  │
│  │  - store(): Process registration                     │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────┬───────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────┐
│                    CLI Layer                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  CreateAdminUser Command                              │  │
│  │  - Accepts: name, email, password                    │  │
│  │  - Validates email uniqueness                        │  │
│  │  - Creates user with hashed password                 │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────┬───────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Data Layer                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  User Model (Eloquent)                                │  │
│  │  - Standard Laravel User model                       │  │
│  │  - No modifications needed                           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Configuration Flow

```
.env file
  ALLOW_PUBLIC_REGISTRATION=true
         │
         ▼
config/auth.php
  'allow_public_registration' => env('ALLOW_PUBLIC_REGISTRATION', false)
         │
         ▼
RegistrationEnabled Middleware
  config('auth.allow_public_registration')
         │
         ├─── true ──▶ Allow access to registration
         │
         └─── false ─▶ Return 403 Forbidden
```

## Components and Interfaces

### 1. RegistrationEnabled Middleware

**Purpose:** Intercept registration requests and enforce the registration policy.

**Location:** `app/Http/Middleware/RegistrationEnabled.php`

**Interface:**
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response;
}
```

**Behavior:**
- Reads `config('auth.allow_public_registration')`
- If `false` or not set, returns 403 response with appropriate message
- If `true`, allows request to proceed to controller

### 2. CreateAdminUser Artisan Command

**Purpose:** Provide CLI interface for creating admin users without web registration.

**Location:** `app/Console/Commands/CreateAdminUser.php`

**Signature:** `user:create-admin`

**Interface:**
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'user:create-admin
                            {--name= : The name of the user}
                            {--email= : The email address of the user}
                            {--password= : The password for the user}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int;
}
```

**Behavior:**
- Accepts optional parameters: `--name`, `--email`, `--password`
- If parameters not provided, prompts interactively
- Validates email uniqueness against database
- Hashes password using `Hash::make()`
- Creates user record
- Returns success/failure status code

### 3. Configuration Updates

**File:** `config/auth.php`

**Addition:**
```php
'allow_public_registration' => env('ALLOW_PUBLIC_REGISTRATION', false),
```

**File:** `.env.example`

**Addition:**
```
# Public Registration
# Set to true to allow public user registration
# Default: false (registration disabled)
ALLOW_PUBLIC_REGISTRATION=false
```

### 4. Route Modifications

**File:** `routes/auth.php`

**Changes:**
- Apply `RegistrationEnabled` middleware to registration routes
- No changes to other authentication routes

```php
Route::middleware(['guest', 'registration.enabled'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);
});
```

### 5. Middleware Registration

**File:** `bootstrap/app.php`

**Addition:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'registration.enabled' => \App\Http\Middleware\RegistrationEnabled::class,
    ]);
})
```

## Data Models

No changes to existing data models are required. The solution uses the existing `User` model without modifications.

**Existing User Model:**
- Table: `users`
- Fields: `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`
- No schema changes needed


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Registration endpoints blocked when disabled

*For any* HTTP request (GET or POST) to registration endpoints when `config('auth.allow_public_registration')` is false, the system should return a 403 Forbidden response.

**Validates: Requirements 1.2, 1.3**

### Property 2: Registration endpoints accessible when enabled

*For any* valid registration request when `config('auth.allow_public_registration')` is true, the system should process the request normally and create the user account.

**Validates: Requirements 1.4**

### Property 3: Only true enables registration

*For any* configuration value other than boolean `true` (including 'false', '', '0', null, 'yes', '1'), the system should treat registration as disabled.

**Validates: Requirements 1.5**

### Property 4: Admin command creates users with valid data

*For any* valid combination of name, email, and password provided to the `user:create-admin` command, the system should successfully create a user account with those credentials.

**Validates: Requirements 2.1**

### Property 5: Admin command rejects duplicate emails

*For any* email address that already exists in the users table, attempting to create a new user with that email via the `user:create-admin` command should fail with an appropriate error message.

**Validates: Requirements 2.2**

### Property 6: Admin command prompts for missing parameters

*For any* subset of required parameters (name, email, password) that are not provided to the `user:create-admin` command, the system should interactively prompt for the missing values.

**Validates: Requirements 2.3**

### Property 7: Passwords are always hashed

*For any* password provided to the `user:create-admin` command, the value stored in the database should be a hashed version, not the plaintext password.

**Validates: Requirements 2.4**

### Property 8: Command output excludes passwords

*For any* user successfully created via the `user:create-admin` command, the confirmation message should include the user's name and email but should not include the password.

**Validates: Requirements 2.5**

### Property 9: Login unaffected by registration setting

*For any* valid user credentials, authentication should succeed regardless of the `config('auth.allow_public_registration')` value.

**Validates: Requirements 4.1**

### Property 10: Profile updates unaffected by registration setting

*For any* authenticated user and valid profile update data, the profile update should succeed regardless of the `config('auth.allow_public_registration')` value.

**Validates: Requirements 4.2**

### Property 11: Password changes unaffected by registration setting

*For any* authenticated user and valid new password, the password change should succeed regardless of the `config('auth.allow_public_registration')` value.

**Validates: Requirements 4.3**

### Property 12: Password resets unaffected by registration setting

*For any* valid user email, the password reset flow should function correctly regardless of the `config('auth.allow_public_registration')` value.

**Validates: Requirements 4.4**

## Error Handling

### Middleware Error Handling

When registration is disabled, the `RegistrationEnabled` middleware should:
- Return HTTP 403 Forbidden status
- Provide a clear error message: "Public registration is currently disabled"
- Log the attempt for security monitoring (optional)
- Not expose internal configuration details

### Command Error Handling

The `CreateAdminUser` command should handle:

**Duplicate Email:**
- Check email existence before attempting creation
- Display error: "A user with email {email} already exists"
- Return exit code 1

**Validation Errors:**
- Validate email format
- Validate password meets minimum requirements
- Display specific validation error messages
- Return exit code 1

**Database Errors:**
- Catch database connection errors
- Display user-friendly error message
- Log technical details for debugging
- Return exit code 1

**Success:**
- Display confirmation with user details
- Return exit code 0

### Configuration Error Handling

**Missing Configuration:**
- Default to `false` (disabled) if `ALLOW_PUBLIC_REGISTRATION` not set
- No error thrown, silent default to secure state

**Invalid Configuration Values:**
- Treat any non-boolean-true value as `false`
- No error thrown, coerce to boolean

## Testing Strategy

### Unit Testing

Unit tests will verify individual components in isolation:

**RegistrationEnabled Middleware Tests:**
- Test middleware allows request when config is `true`
- Test middleware blocks request when config is `false`
- Test middleware blocks request when config is not set
- Test middleware returns correct 403 response structure

**CreateAdminUser Command Tests:**
- Test command creates user with provided parameters
- Test command prompts for missing parameters
- Test command validates email format
- Test command checks for duplicate emails
- Test command hashes passwords
- Test command displays correct output
- Test command returns correct exit codes

**Configuration Tests:**
- Test config defaults to `false` when env var not set
- Test config reads `true` correctly
- Test config treats non-true values as `false`

### Property-Based Testing

Property-based tests will verify universal properties across many inputs using a PHP property-based testing library. We will use **Pest with Pest Arch** for property-based testing, as it integrates well with Laravel and PHPUnit.

**Configuration:**
- Each property-based test should run a minimum of 100 iterations
- Each test must be tagged with a comment referencing the correctness property
- Tag format: `**Feature: controlled-registration, Property {number}: {property_text}**`

**Property Tests to Implement:**

1. **Property 1: Registration endpoints blocked when disabled**
   - Generate: Various HTTP requests to registration routes
   - Invariant: All return 403 when config is false
   - Tag: `**Feature: controlled-registration, Property 1: Registration endpoints blocked when disabled**`

2. **Property 2: Registration endpoints accessible when enabled**
   - Generate: Valid registration data
   - Invariant: User created successfully when config is true
   - Tag: `**Feature: controlled-registration, Property 2: Registration endpoints accessible when enabled**`

3. **Property 3: Only true enables registration**
   - Generate: Various non-true config values ('false', '', '0', null, 'yes', '1', etc.)
   - Invariant: All result in registration being disabled
   - Tag: `**Feature: controlled-registration, Property 3: Only true enables registration**`

4. **Property 4: Admin command creates users with valid data**
   - Generate: Random valid names, emails, passwords
   - Invariant: User created with correct data
   - Tag: `**Feature: controlled-registration, Property 4: Admin command creates users with valid data**`

5. **Property 5: Admin command rejects duplicate emails**
   - Generate: Existing user emails
   - Invariant: Command fails with appropriate error
   - Tag: `**Feature: controlled-registration, Property 5: Admin command rejects duplicate emails**`

6. **Property 6: Admin command prompts for missing parameters**
   - Generate: Various combinations of missing parameters
   - Invariant: Command prompts for all missing values
   - Tag: `**Feature: controlled-registration, Property 6: Admin command prompts for missing parameters**`

7. **Property 7: Passwords are always hashed**
   - Generate: Random passwords
   - Invariant: Stored password ≠ plaintext password and Hash::check() returns true
   - Tag: `**Feature: controlled-registration, Property 7: Passwords are always hashed**`

8. **Property 8: Command output excludes passwords**
   - Generate: Random user data
   - Invariant: Output contains name and email but not password
   - Tag: `**Feature: controlled-registration, Property 8: Command output excludes passwords**`

9. **Property 9: Login unaffected by registration setting**
   - Generate: Valid user credentials, various registration config values
   - Invariant: Login succeeds regardless of config
   - Tag: `**Feature: controlled-registration, Property 9: Login unaffected by registration setting**`

10. **Property 10: Profile updates unaffected by registration setting**
    - Generate: Authenticated users, valid profile data, various registration config values
    - Invariant: Profile updates succeed regardless of config
    - Tag: `**Feature: controlled-registration, Property 10: Profile updates unaffected by registration setting**`

11. **Property 11: Password changes unaffected by registration setting**
    - Generate: Authenticated users, valid new passwords, various registration config values
    - Invariant: Password changes succeed regardless of config
    - Tag: `**Feature: controlled-registration, Property 11: Password changes unaffected by registration setting**`

12. **Property 12: Password resets unaffected by registration setting**
    - Generate: Valid user emails, various registration config values
    - Invariant: Password reset flow works regardless of config
    - Tag: `**Feature: controlled-registration, Property 12: Password resets unaffected by registration setting**`

### Integration Testing

Integration tests will verify the complete flow:

**Registration Flow Tests:**
- Test complete registration flow when enabled
- Test registration blocked at route level when disabled
- Test registration blocked at controller level when disabled

**Admin User Creation Flow:**
- Test complete user creation via command
- Test user can login after creation via command
- Test created user has correct permissions

**Authentication Flow Tests:**
- Test login flow with registration disabled
- Test password reset flow with registration disabled
- Test profile management with registration disabled

### Test Execution Strategy

1. **Implementation-first approach:** Implement features before writing corresponding tests
2. **Core functionality validation:** Validate middleware and command functionality early
3. **Property tests after unit tests:** Write property-based tests after unit tests pass
4. **Integration tests last:** Verify complete flows after individual components work

### Manual Testing Checklist

After automated tests pass, manually verify:
- [ ] Registration page returns 403 when disabled
- [ ] Registration page accessible when enabled
- [ ] Admin command creates functional user accounts
- [ ] Created users can login successfully
- [ ] Existing auth flows unaffected
- [ ] `.env.example` documentation is clear
- [ ] Command help text is comprehensive
