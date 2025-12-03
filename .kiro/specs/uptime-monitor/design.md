# Design Document

## Overview

The uptime monitoring application is a Laravel 12-based web application that enables administrators to monitor website availability through periodic HTTP checks. The system uses a queue-based architecture to perform checks asynchronously, stores check results in a relational database, and sends notifications via email and Pushover when monitor status changes occur.

The application follows Laravel's MVC architecture with additional service and repository layers for business logic separation. The frontend uses Blade templates with Tailwind CSS for styling and minimal JavaScript for interactivity. Background processing is handled by Laravel's queue system with database driver for simplicity.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Web Interface                          │
│  (Blade Templates + Tailwind CSS + Alpine.js)              │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                    Controllers                              │
│  (Authentication, Monitor CRUD, Dashboard)                  │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                  Service Layer                              │
│  (MonitorService, CheckService, NotificationService)        │
└─────┬──────────────┬──────────────────┬────────────────────┘
      │              │                  │
┌─────▼──────┐  ┌───▼──────┐  ┌────────▼─────────┐
│ Repository │  │  Queue   │  │  Notifications   │
│   Layer    │  │  Jobs    │  │  (Email/Pushover)│
└─────┬──────┘  └───┬──────┘  └──────────────────┘
      │              │
┌─────▼──────────────▼──────────────────────────────────────┐
│                    Database                                │
│  (users, monitors, checks, jobs, failed_jobs)             │
└───────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

1. **Monitor Creation**: Admin → Controller → Service → Repository → Database
2. **Check Execution**: Scheduler → Queue Job → CheckService → HTTP Client → Database
3. **Status Change**: CheckService → NotificationService → Email/Pushover API
4. **Dashboard View**: Admin → Controller → Service → Repository → View

## Components and Interfaces

### Models

#### User Model
```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    
    public function monitors(): HasMany;
}
```

#### Monitor Model
```php
class Monitor extends Model
{
    protected $fillable = [
        'user_id', 'name', 'url', 'check_interval_minutes', 
        'status', 'last_checked_at', 'last_status_change_at'
    ];
    
    protected $casts = [
        'last_checked_at' => 'datetime',
        'last_status_change_at' => 'datetime',
    ];
    
    public function user(): BelongsTo;
    public function checks(): HasMany;
    public function isUp(): bool;
    public function isDown(): bool;
    public function isPending(): bool;
}
```

#### Check Model
```php
class Check extends Model
{
    protected $fillable = [
        'monitor_id', 'status', 'status_code', 
        'response_time_ms', 'error_message', 'checked_at'
    ];
    
    protected $casts = [
        'checked_at' => 'datetime',
        'response_time_ms' => 'integer',
    ];
    
    public function monitor(): BelongsTo;
    public function wasSuccessful(): bool;
    public function wasFailed(): bool;
}
```

### Services

#### MonitorService
Handles monitor CRUD operations and business logic.

```php
interface MonitorServiceInterface
{
    public function createMonitor(User $user, array $data): Monitor;
    public function updateMonitor(Monitor $monitor, array $data): Monitor;
    public function deleteMonitor(Monitor $monitor): bool;
    public function getAllMonitorsForUser(User $user): Collection;
    public function getMonitorWithStats(Monitor $monitor): array;
}
```

#### CheckService
Executes HTTP checks and records results.

```php
interface CheckServiceInterface
{
    public function performCheck(Monitor $monitor): Check;
    public function scheduleNextCheck(Monitor $monitor): void;
    public function calculateUptime(Monitor $monitor, int $hours): float;
}
```

#### NotificationService
Sends notifications via configured channels.

```php
interface NotificationServiceInterface
{
    public function notifyStatusChange(Monitor $monitor, string $oldStatus, string $newStatus): void;
    public function sendEmailNotification(Monitor $monitor, string $status): void;
    public function sendPushoverNotification(Monitor $monitor, string $status, int $priority): void;
}
```

### Controllers

#### DashboardController
```php
class DashboardController extends Controller
{
    public function index(): View;
}
```

#### MonitorController
```php
class MonitorController extends Controller
{
    public function index(): View;
    public function create(): View;
    public function store(StoreMonitorRequest $request): RedirectResponse;
    public function show(Monitor $monitor): View;
    public function edit(Monitor $monitor): View;
    public function update(UpdateMonitorRequest $request, Monitor $monitor): RedirectResponse;
    public function destroy(Monitor $monitor): RedirectResponse;
}
```

### Queue Jobs

#### PerformMonitorCheck
```php
class PerformMonitorCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(public Monitor $monitor) {}
    
    public function handle(CheckService $checkService): void;
}
```

#### ScheduleMonitorChecks
```php
class ScheduleMonitorChecks implements ShouldQueue
{
    public function handle(MonitorService $monitorService): void;
}
```

### Form Requests

#### StoreMonitorRequest
```php
class StoreMonitorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'check_interval_minutes' => 'required|integer|min:1|max:1440',
        ];
    }
}
```

#### UpdateMonitorRequest
```php
class UpdateMonitorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|url|max:2048',
            'check_interval_minutes' => 'sometimes|required|integer|min:1|max:1440',
        ];
    }
}
```

## Data Models

### Database Schema

#### users table
```
id: bigint (PK)
name: string
email: string (unique)
password: string
email_verified_at: timestamp (nullable)
remember_token: string (nullable)
created_at: timestamp
updated_at: timestamp
```

#### monitors table
```
id: bigint (PK)
user_id: bigint (FK → users.id)
name: string
url: string
check_interval_minutes: integer (default: 5)
status: enum('up', 'down', 'pending') (default: 'pending')
last_checked_at: timestamp (nullable)
last_status_change_at: timestamp (nullable)
created_at: timestamp
updated_at: timestamp

indexes:
  - user_id
  - status
  - last_checked_at
```

#### checks table
```
id: bigint (PK)
monitor_id: bigint (FK → monitors.id, cascade delete)
status: enum('success', 'failed')
status_code: integer (nullable)
response_time_ms: integer (nullable)
error_message: text (nullable)
checked_at: timestamp
created_at: timestamp

indexes:
  - monitor_id
  - checked_at
  - (monitor_id, checked_at) composite
```

#### notification_settings table
```
id: bigint (PK)
user_id: bigint (FK → users.id)
email_enabled: boolean (default: true)
email_address: string (nullable)
pushover_enabled: boolean (default: false)
pushover_user_key: string (nullable)
pushover_api_token: string (nullable)
created_at: timestamp
updated_at: timestamp

indexes:
  - user_id (unique)
```

### Entity Relationships

- User has many Monitors (one-to-many)
- Monitor belongs to User (many-to-one)
- Monitor has many Checks (one-to-many)
- Check belongs to Monitor (many-to-one)
- User has one NotificationSettings (one-to-one)

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Authentication protects dashboard access
*For any* unauthenticated request to dashboard routes, the system should redirect to the login page and not display protected content.
**Validates: Requirements 1.1**

### Property 2: Valid credentials grant access
*For any* valid administrator credentials submitted to the login form, the system should authenticate the user and allow access to the dashboard.
**Validates: Requirements 1.2**

### Property 3: Invalid credentials are rejected
*For any* invalid credentials submitted to the login form, the system should reject authentication and display an error without granting access.
**Validates: Requirements 1.3**

### Property 4: Monitor creation with valid URL succeeds
*For any* valid URL and monitor data submitted by an authenticated administrator, the system should create a monitor record with status 'pending'.
**Validates: Requirements 2.1**

### Property 5: Monitor creation with invalid URL fails
*For any* invalid URL format submitted in monitor creation (non-HTTP/HTTPS protocols, localhost, missing TLD, malformed URLs), the system should reject the submission and return validation errors.
**Validates: Requirements 2.2, 2.3, 2.4**

### Property 6: Dashboard displays all user monitors
*For any* authenticated administrator viewing the dashboard, all monitors belonging to that user should be displayed with their current status.
**Validates: Requirements 3.1**

### Property 7: Monitor updates preserve history
*For any* monitor update operation, the check history associated with that monitor should remain unchanged and accessible.
**Validates: Requirements 4.3**

### Property 8: Monitor deletion removes all data
*For any* monitor deletion, all associated check records should be removed from the database.
**Validates: Requirements 5.1**

### Property 9: Successful HTTP responses mark monitors as up
*For any* HTTP check that receives a 2xx status code, the system should record the check as successful and set monitor status to 'up'.
**Validates: Requirements 6.2**

### Property 10: Failed HTTP responses mark monitors as down
*For any* HTTP check that receives a 4xx or 5xx status code, the system should record the check as failed and set monitor status to 'down'.
**Validates: Requirements 6.3**

### Property 11: Timeout failures mark monitors as down
*For any* HTTP check that exceeds 30 seconds without response, the system should record the check as failed with a timeout error.
**Validates: Requirements 6.4**

### Property 12: Status transitions trigger notifications
*For any* monitor that changes from 'up' to 'down' or 'down' to 'up', the system should send notifications to all enabled channels.
**Validates: Requirements 7.1, 7.2**

### Property 13: Stable status prevents duplicate notifications
*For any* monitor that remains in the same status across consecutive checks, the system should not send additional notifications.
**Validates: Requirements 7.3, 7.4**

### Property 14: First check does not trigger notification
*For any* newly created monitor completing its first check, the system should not send a notification regardless of the result.
**Validates: Requirements 7.5**

### Property 15: Email notifications include required details
*For any* email notification sent, the message should contain the monitor name, URL, status change, and timestamp.
**Validates: Requirements 8.2**

### Property 16: Pushover notifications use correct priority
*For any* Pushover notification for a down status, the system should set priority to high (2), and for recovery status, priority should be normal (0).
**Validates: Requirements 9.4, 9.5**

### Property 17: Uptime calculation accuracy
*For any* monitor with check history, the uptime percentage should equal (successful checks / total checks) × 100 for the specified time period.
**Validates: Requirements 11.4**

### Property 18: Checks execute asynchronously
*For any* scheduled check, the HTTP request should execute in a queued job outside the web request lifecycle.
**Validates: Requirements 12.1, 12.2**

## Error Handling

### HTTP Check Failures
- **Connection Timeout**: Record as failed check with error message "Connection timeout after 30 seconds"
- **DNS Resolution Failure**: Record as failed check with error message "Unable to resolve hostname"
- **SSL Certificate Error**: Record as failed check with error message "SSL certificate validation failed"
- **Network Error**: Record as failed check with specific error message from HTTP client

### Queue Job Failures
- **Check Job Exception**: Log error, do not retry, schedule next check normally
- **Notification Job Exception**: Log error, do not block check recording, retry up to 3 times
- **Database Connection Loss**: Retry job automatically via Laravel's queue retry mechanism

### Validation Errors
- **Invalid URL Format**: Return 422 with specific validation error message
- **Missing Required Fields**: Return 422 with field-specific error messages
- **Duplicate Monitor URL**: Allow duplicates (user may want to monitor same URL with different intervals)

### Authentication Errors
- **Invalid Credentials**: Return to login with "Invalid credentials" message
- **Session Expiration**: Redirect to login with "Session expired" message
- **CSRF Token Mismatch**: Return 419 with "Page expired" message

### Authorization Errors
- **Accessing Another User's Monitor**: Return 403 Forbidden
- **Unauthenticated Access**: Redirect to login page

## Testing Strategy

### Unit Testing

The application will use PHPUnit for unit testing with the following focus areas:

**Model Tests**:
- Test model relationships (User → Monitors, Monitor → Checks)
- Test model methods (isUp(), isDown(), isPending())
- Test model scopes and query builders

**Service Tests**:
- Test MonitorService CRUD operations with mocked repositories
- Test CheckService HTTP request handling with mocked HTTP client
- Test NotificationService with mocked email and Pushover clients
- Test uptime calculation logic with known check data

**Validation Tests**:
- Test form request validation rules
- Test URL format validation
- Test check interval bounds validation

**Example Unit Tests**:
- Test that a monitor with status 'up' returns true for isUp()
- Test that uptime calculation returns 100% when all checks succeed
- Test that uptime calculation returns 0% when all checks fail
- Test that invalid URL format fails validation
- Test that check interval below 1 minute fails validation

### Property-Based Testing

The application will use PHPUnit with custom generators for property-based testing. Since PHP doesn't have a mature property-based testing library like Hypothesis (Python) or QuickCheck (Haskell), we'll implement a lightweight generator approach using PHPUnit data providers with randomized inputs.

**Property Testing Configuration**:
- Each property test will run with at least 100 randomly generated inputs
- Tests will use seeded random generation for reproducibility
- Failed tests will output the specific input that caused failure

**Property Test Implementation**:
- Create custom test data generators for URLs, status codes, timestamps
- Use PHPUnit data providers to supply multiple test cases
- Tag each property test with a comment referencing the design document property

**Example Property Tests**:
- Property 9: Generate random 2xx status codes, verify all mark monitor as 'up'
- Property 10: Generate random 4xx/5xx status codes, verify all mark monitor as 'down'
- Property 17: Generate random check histories, verify uptime calculation formula
- Property 13: Generate sequences of identical statuses, verify no duplicate notifications

### Integration Testing

**Feature Tests**:
- Test complete user flows (login → create monitor → view dashboard)
- Test monitor CRUD operations through HTTP requests
- Test queue job execution with database assertions
- Test notification sending with fake mail and HTTP client

**Database Tests**:
- Test migrations create correct schema
- Test foreign key constraints and cascading deletes
- Test database transactions and rollbacks

### End-to-End Testing

**Browser Tests** (optional, using Laravel Dusk):
- Test complete authentication flow
- Test monitor creation and editing through UI
- Test dashboard displays correct monitor statuses
- Test responsive design on mobile viewports

## Security Considerations

### Authentication & Authorization
- Use Laravel's built-in authentication system
- Implement middleware to protect all dashboard routes
- Use policy classes to ensure users can only access their own monitors
- Hash passwords using bcrypt (Laravel default)

### Input Validation
- Validate all user inputs using Form Requests
- Sanitize URLs before storing in database
- Limit URL length to prevent database overflow
- Validate check intervals to prevent abuse (1-1440 minutes)

### API Security
- Store Pushover API tokens encrypted in database
- Never expose API tokens in frontend code or logs
- Use environment variables for sensitive configuration
- Implement rate limiting on login attempts

### CSRF Protection
- Use Laravel's CSRF protection on all forms
- Include @csrf directive in all Blade forms
- Validate CSRF tokens on all POST/PUT/DELETE requests

### SQL Injection Prevention
- Use Eloquent ORM for all database queries
- Use parameter binding for any raw queries
- Never concatenate user input into SQL strings

## Performance Considerations

### Database Optimization
- Index foreign keys (user_id, monitor_id)
- Index frequently queried columns (status, last_checked_at)
- Use composite index on (monitor_id, checked_at) for check history queries
- Implement pagination for check history (50 records per page)
- Consider archiving old checks after 90 days

### Queue Optimization
- Use database queue driver for simplicity (can migrate to Redis later)
- Process checks concurrently using multiple queue workers
- Set reasonable timeout for HTTP checks (30 seconds)
- Implement job batching for scheduling multiple checks

### Caching Strategy
- Cache uptime statistics for 5 minutes to reduce calculation overhead
- Cache monitor counts per user for dashboard display
- Use Laravel's cache facade with database driver initially
- Consider Redis cache for production deployments

### HTTP Client Optimization
- Use Guzzle HTTP client with connection pooling
- Set reasonable timeouts (30 seconds)
- Disable SSL verification option for development (configurable)
- Implement retry logic for transient network failures (max 2 retries)

## Deployment Considerations

### Environment Configuration
- Document all required environment variables in .env.example
- Require MAIL_* configuration for email notifications
- Require PUSHOVER_* configuration for Pushover notifications
- Set APP_ENV=production for production deployments

### Queue Worker Management
- Document queue worker setup in deployment guide
- Recommend using Supervisor for queue worker process management
- Configure queue worker to restart on failure
- Set up monitoring for queue worker health

### Scheduler Configuration
- Configure Laravel scheduler to run every minute
- Schedule ScheduleMonitorChecks job to run every minute
- Document cron configuration in deployment guide

### Database Migrations
- Run migrations during deployment process
- Test migrations on staging environment first
- Implement rollback plan for failed migrations

## Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: SQLite (development), MySQL 8.4 (production via DDEV)
- **Queue**: Database driver (can migrate to Redis)
- **HTTP Client**: Guzzle (included with Laravel)
- **Testing**: PHPUnit 11.5+

### Frontend
- **Templating**: Blade
- **CSS**: Tailwind CSS 4
- **JavaScript**: Alpine.js (for minimal interactivity)
- **Build Tool**: Vite 7

### External Services
- **Email**: Laravel Mail (SMTP configuration)
- **Push Notifications**: Pushover API (https://pushover.net/api)

### Development Tools
- **Local Environment**: DDEV
- **Code Style**: Laravel Pint (PSR-12)
- **Package Manager**: Composer 2

## Future Enhancements

### Phase 2 Features (Not in Current Scope)
- Multiple notification channels per monitor
- Custom HTTP headers for checks
- HTTP method selection (GET, POST, HEAD)
- Expected status code configuration
- Response body content validation
- SSL certificate expiration monitoring
- Public status pages
- Multi-user teams and permissions
- API for programmatic access
- Webhook notifications
- Slack/Discord integrations
- Mobile app
- Advanced alerting rules (e.g., alert after N consecutive failures)
- Maintenance windows (suppress alerts during scheduled maintenance)
- Monitor groups and tags
- Custom check intervals per monitor
- Incident management and postmortems
