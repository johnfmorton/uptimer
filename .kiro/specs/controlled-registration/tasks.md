# Implementation Plan

- [x] 1. Add configuration for registration control
  - Add `allow_public_registration` setting to `config/auth.php` with default value of `false`
  - Update `.env.example` with `ALLOW_PUBLIC_REGISTRATION` variable and descriptive comments
  - _Requirements: 1.1, 3.1, 3.2_

- [x] 2. Create and configure RegistrationEnabled middleware
  - Create `app/Http/Middleware/RegistrationEnabled.php` middleware class
  - Implement logic to check `config('auth.allow_public_registration')`
  - Return 403 Forbidden response when registration is disabled
  - Register middleware alias in `bootstrap/app.php` as `registration.enabled`
  - _Requirements: 1.2, 1.3, 1.4, 1.5_

- [ ]* 2.1 Write property test for middleware blocking when disabled
  - **Property 1: Registration endpoints blocked when disabled**
  - **Validates: Requirements 1.2, 1.3**

- [ ]* 2.2 Write property test for middleware allowing when enabled
  - **Property 2: Registration endpoints accessible when enabled**
  - **Validates: Requirements 1.4**

- [ ]* 2.3 Write property test for non-true values disabling registration
  - **Property 3: Only true enables registration**
  - **Validates: Requirements 1.5**

- [x] 3. Apply middleware to registration routes
  - Update `routes/auth.php` to apply `registration.enabled` middleware to registration routes
  - Ensure other authentication routes (login, password reset, profile) remain unchanged
  - _Requirements: 1.2, 1.3, 4.1, 4.2, 4.3, 4.4_

- [ ]* 3.1 Write unit tests for route middleware application
  - Test registration routes have middleware applied
  - Test other auth routes do not have registration middleware
  - _Requirements: 1.2, 1.3_

- [x] 4. Create CreateAdminUser artisan command
  - Generate command using `ddev artisan make:command CreateAdminUser`
  - Set command signature to `user:create-admin` with optional parameters: `--name`, `--email`, `--password`
  - Set command description to "Create a new admin user"
  - Implement interactive prompts for missing parameters
  - Add email validation and uniqueness check
  - Hash password before storing using `Hash::make()`
  - Create user record in database
  - Display confirmation message with user details (excluding password)
  - Return appropriate exit codes (0 for success, 1 for failure)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.3_

- [ ]* 4.1 Write property test for command creating users with valid data
  - **Property 4: Admin command creates users with valid data**
  - **Validates: Requirements 2.1**

- [ ]* 4.2 Write property test for command rejecting duplicate emails
  - **Property 5: Admin command rejects duplicate emails**
  - **Validates: Requirements 2.2**

- [ ]* 4.3 Write property test for command prompting for missing parameters
  - **Property 6: Admin command prompts for missing parameters**
  - **Validates: Requirements 2.3**

- [ ]* 4.4 Write property test for password hashing
  - **Property 7: Passwords are always hashed**
  - **Validates: Requirements 2.4**

- [ ]* 4.5 Write property test for command output excluding passwords
  - **Property 8: Command output excludes passwords**
  - **Validates: Requirements 2.5**

- [ ]* 4.6 Write unit tests for command error handling
  - Test command handles database errors gracefully
  - Test command validates email format
  - Test command validates password requirements
  - Test command returns correct exit codes
  - _Requirements: 2.1, 2.2, 3.3_

- [ ] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ]* 6. Write property tests for authentication independence
  - Write tests to verify existing auth features work regardless of registration setting
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ]* 6.1 Write property test for login unaffected by registration setting
  - **Property 9: Login unaffected by registration setting**
  - **Validates: Requirements 4.1**

- [ ]* 6.2 Write property test for profile updates unaffected by registration setting
  - **Property 10: Profile updates unaffected by registration setting**
  - **Validates: Requirements 4.2**

- [ ]* 6.3 Write property test for password changes unaffected by registration setting
  - **Property 11: Password changes unaffected by registration setting**
  - **Validates: Requirements 4.3**

- [ ]* 6.4 Write property test for password resets unaffected by registration setting
  - **Property 12: Password resets unaffected by registration setting**
  - **Validates: Requirements 4.4**

- [ ]* 7. Write integration tests for complete flows
  - Test complete registration flow when enabled
  - Test registration blocked at route level when disabled
  - Test user creation via command and subsequent login
  - _Requirements: 1.2, 1.3, 1.4, 2.1, 4.1_

- [ ] 8. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
