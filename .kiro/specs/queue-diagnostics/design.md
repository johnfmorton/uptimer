# Design Document: Queue Diagnostics

## Overview

This feature enhances the uptime monitoring application with comprehensive queue and scheduler diagnostic capabilities. The design addresses the critical issue where users cannot easily determine if their background job processing infrastructure is properly configured and running.

The solution provides:
- Real-time queue health monitoring with actionable diagnostics
- Manual monitor check triggering for immediate verification
- Automatic dashboard updates without page reloads
- Clear visual indicators for queue worker and scheduler status
- Test job functionality to verify queue processing

This design integrates seamlessly with the existing Laravel application architecture, leveraging the current service layer pattern, job queue system, and Blade component structure.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Dashboard UI                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Queue Status │  │ Test Queue   │  │ Check Now    │      │
│  │ Widget       │  │ Button       │  │ Buttons      │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    HTTP Controllers                          │
│  ┌──────────────────┐  ┌──────────────────────────────┐    │
│  │ DashboardController│  │ QueueDiagnosticsController │    │
│  │ MonitorController  │  │                            │    │
│  └──────────────────┘  └──────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     Service Layer                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         QueueDiagnosticsService                       │  │
│  │  - getQueueStatus()                                   │  │
│  │  - getSchedulerStatus()                               │  │
│  │  - getPendingJobsCount()                              │  │
│  │  - getFailedJobsCount()                               │  │
│  │  - getStuckJobsCount()                                │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Job Queue                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ TestQueueJob │  │PerformMonitor│  │ Queue Worker │      │
│  │              │  │Check         │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Database                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ jobs         │  │ failed_jobs  │  │ monitors     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

1. **Queue Status Check Flow**:
   - User views Dashboard
   - DashboardController calls QueueDiagnosticsService
   - Service queries jobs and failed_jobs tables
   - Service checks for scheduler heartbeat
   - Dashboard renders status widget with diagnostics

2. **Test Queue Flow**:
   - User clicks "Test Queue" button
   - QueueDiagnosticsController dispatches TestQueueJob
   - Job is added to jobs table
   - Queue Worker processes job (if running)
   - Job logs success message
   - User checks logs to verify

3. **Manual Check Flow**:
   - User clicks "Check Now" on monitor
   - MonitorController dispatches PerformMonitorCheck job
   - Job is queued and processed
   - Monitor status and last_checked_at updated
   - Dashboard auto-refreshes to show update

4. **Auto-Refresh Flow**:
   - Dashboard JavaScript polls every 30 seconds
   - AJAX request to API endpoint
   - Returns updated monitor data
   - JavaScript updates DOM without page reload
   - Scroll position preserved

## Components and Interfaces

### 1. QueueDiagnosticsService

**Purpose**: Centralize all queue health checking logic

**Location**: `app/Services/QueueDiagnosticsService.php`

**Public Methods**:

```php
class QueueDiagnosticsService
{
    /**
     * Get comprehensive queue diagnostics.
     *
     * @return array{
     *     pending_jobs: int,
     *     failed_jobs_last_hour: int,
     *     stuck_jobs: int,
     *     queue_worker_running: bool,
     *     scheduler_running: bool,
     *     has_issues: bool
     * }
     */
    public function getQueueDiagnostics(): array;

    /**
     * Get count of pending jobs in the queue.
     *
     * @return int
     */
    public function getPendingJobsCount(): int;

    /**
     * Get count of failed jobs in the last hour.
     *
     * @return int
     */
    public function getFailedJobsCount(): int;

    /**
     * Get count of stuck jobs (pending > 5 minutes).
     *
     * @return int
     */
    public function getStuckJobsCount(): int;

    /**
     * Check if queue worker appears to be running.
     *
     * @return bool
     */
    public function isQueueWorkerRunning(): bool;

    /**
     * Check if scheduler appears to be running.
     *
     * @return bool
     */
    public function isSchedulerRunning(): bool;

    /**
     * Dispatch a test job to verify queue functionality.
     *
     * @return void
     */
    public function dispatchTestJob(): void;
}
```

**Implementation Details**:
- Query `jobs` table for pending and stuck job counts
- Query `failed_jobs` table with timestamp filter for last hour
- Use cache-based heartbeat mechanism to detect scheduler status
- Infer queue worker status from stuck jobs count
- Leverage existing TestQueueJob for test dispatching

### 2. QueueDiagnosticsController

**Purpose**: Handle HTTP requests for queue diagnostics and test job dispatching

**Location**: `app/Http/Controllers/QueueDiagnosticsController.php`

**Routes**:
- `POST /queue/test` - Dispatch test job
- `GET /api/queue/status` - Get queue diagnostics (JSON)

**Methods**:

```php
class QueueDiagnosticsController extends Controller
{
    public function __construct(
        private QueueDiagnosticsService $diagnosticsService
    ) {}

    /**
     * Dispatch a test job to the queue.
     *
     * @return RedirectResponse
     */
    public function testQueue(): RedirectResponse;

    /**
     * Get queue status as JSON for AJAX polling.
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse;
}
```

### 3. MonitorController Enhancement

**Purpose**: Add manual check triggering capability

**Location**: `app/Http/Controllers/MonitorController.php` (existing, to be enhanced)

**New Route**:
- `POST /monitors/{monitor}/check` - Trigger manual check

**New Method**:

```php
/**
 * Manually trigger a check for a specific monitor.
 *
 * @param  Monitor  $monitor
 * @return RedirectResponse
 */
public function triggerCheck(Monitor $monitor): RedirectResponse;
```

### 4. DashboardController Enhancement

**Purpose**: Pass queue diagnostics to dashboard view

**Location**: `app/Http/Controllers/DashboardController.php` (existing, to be enhanced)

**Enhancement**:
- Inject QueueDiagnosticsService
- Call `getQueueDiagnostics()` in index method
- Pass diagnostics data to view

### 5. Dashboard Auto-Refresh JavaScript

**Purpose**: Poll for monitor updates without page reload

**Location**: `resources/js/dashboard-refresh.js` (new file)

**Functionality**:
- Poll `/api/monitors` endpoint every 30 seconds
- Update monitor status badges
- Update last_checked_at timestamps
- Preserve scroll position
- Handle network errors gracefully
- Use `fetch()` API with error handling

### 6. Queue Status Blade Component

**Purpose**: Display queue diagnostics in a reusable component

**Location**: `resources/views/components/queue-status.blade.php` (new file)

**Props**:
- `$diagnostics` - Array of queue diagnostic data

**Display**:
- Queue worker status (running/not running)
- Scheduler status (running/not running)
- Pending jobs count
- Failed jobs count (last hour)
- Stuck jobs count with warning
- Setup instructions when components not running
- "Test Queue" button

## Data Models

### Existing Models (No Changes Required)

**Monitor Model**: Already has all necessary fields
- `id`, `user_id`, `name`, `url`
- `check_interval_minutes`
- `status` (pending/up/down)
- `last_checked_at`
- `last_status_change_at`

**Check Model**: Already tracks check results
- `id`, `monitor_id`, `status`, `status_code`
- `response_time_ms`, `checked_at`

### Database Tables (Existing, No Migrations Needed)

**jobs table**: Laravel default queue table
- `id`, `queue`, `payload`, `attempts`
- `reserved_at`, `available_at`, `created_at`

**failed_jobs table**: Laravel default failed jobs table
- `id`, `uuid`, `connection`, `queue`
- `payload`, `exception`, `failed_at`

### Cache Keys (New)

**scheduler_heartbeat**: Cache key to track scheduler status
- Key: `scheduler:heartbeat`
- Value: Current timestamp
- TTL: 90 seconds (scheduler runs every minute)
- Updated by: Scheduled command that runs every minute

## 
Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Before defining the correctness properties, let me analyze each acceptance criterion for testability:

### Acceptance Criteria Testing Prework

**1.1 WHEN a user clicks a "Test Queue" button on the Dashboard THEN the Application SHALL dispatch a Test Job to the Queue System**
Thoughts: This is about the behavior that should occur for any user clicking the button. We can test this by simulating button clicks and verifying that a job is dispatched to the queue.
Testable: yes - property

**1.2 WHEN a Test Job is dispatched THEN the Dashboard SHALL display a success message with instructions to check logs**
Thoughts: This is about UI feedback that should occur whenever a test job is dispatched. We can test this by dispatching jobs and checking the response.
Testable: yes - property

**1.3 WHEN a Test Job is processed by the Queue Worker THEN the Application SHALL log a message indicating successful queue processing**
Thoughts: This is about logging behavior that should occur for all test jobs. We can test this by processing jobs and checking log output.
Testable: yes - property

**1.4 WHEN the Queue Worker is not running THEN the Test Job SHALL remain in the jobs table with pending status**
Thoughts: This is about persistence behavior when the worker is down. We can test this by dispatching jobs without a worker and checking the database.
Testable: yes - property

**1.5 WHEN a user views the Dashboard THEN the Dashboard SHALL display a "Test Queue" button near the queue status indicator**
Thoughts: This is about UI element presence. We can test this by rendering the dashboard and checking for the button element.
Testable: yes - example

**2.1 WHEN an administrator views the queue status on the Dashboard THEN the Dashboard SHALL indicate whether the Scheduler is running**
Thoughts: This is about displaying scheduler status for any administrator viewing the dashboard. We can test this by rendering the dashboard with different scheduler states.
Testable: yes - property

**2.2 WHEN the Scheduler is not running THEN the Dashboard SHALL display a warning message with instructions to start the Scheduler**
Thoughts: This is about conditional UI display based on scheduler state. We can test this across different scheduler states.
Testable: yes - property

**2.3 WHEN the Scheduler is running THEN the Dashboard SHALL display a success message confirming automatic checks are enabled**
Thoughts: This is about conditional UI display when scheduler is active. We can test this with scheduler in running state.
Testable: yes - property

**2.4 WHEN monitors remain in pending status for a duration exceeding their configured check interval THEN the Dashboard SHALL display a warning indicating the Scheduler may not be running**
Thoughts: This is about detecting stale monitors and displaying warnings. We can test this by creating monitors with various check intervals and last_checked_at timestamps.
Testable: yes - property

**2.5 WHEN displaying Scheduler status THEN the Dashboard SHALL provide the command needed to start the Scheduler**
Thoughts: This is about ensuring specific content is present in the UI. We can test this by rendering the status display and checking for the command string.
Testable: yes - property

**3.1 WHEN a user clicks "Check Now" on a monitor THEN the Application SHALL dispatch a Monitor Check Job for that monitor to the Queue System**
Thoughts: This is about job dispatching behavior that should occur for any monitor. We can test this by triggering checks and verifying job dispatch.
Testable: yes - property

**3.2 WHEN a Monitor Check Job is dispatched THEN the Dashboard SHALL display a confirmation message**
Thoughts: This is about UI feedback for job dispatch. We can test this by dispatching jobs and checking the response.
Testable: yes - property

**3.3 WHEN a Monitor Check Job completes THEN the Application SHALL update the monitor status and last_checked_at timestamp**
Thoughts: This is about state updates that should occur for all completed checks. We can test this by processing jobs and verifying database updates.
Testable: yes - property

**3.4 WHEN a user views a monitor detail page THEN the Dashboard SHALL display a "Check Now" button**
Thoughts: This is about UI element presence on a specific page. We can test this by rendering the page and checking for the button.
Testable: yes - example

**3.5 WHEN a user views the monitors list THEN the Dashboard SHALL display a "Check Now" action for each monitor**
Thoughts: This is about UI elements being present for all monitors in a list. We can test this by rendering lists with various monitor counts.
Testable: yes - property

**4.1 WHEN a user views the queue status on the Dashboard THEN the Dashboard SHALL display the count of pending jobs in the Queue System**
Thoughts: This is about displaying accurate counts for any queue state. We can test this by creating various queue states and verifying the displayed count.
Testable: yes - property

**4.2 WHEN a user views the queue status on the Dashboard THEN the Dashboard SHALL display the count of failed jobs within the last 60 minutes**
Thoughts: This is about displaying accurate failed job counts with time filtering. We can test this with various failed job timestamps.
Testable: yes - property

**4.3 WHEN a user views the queue status on the Dashboard THEN the Dashboard SHALL display the count of Stuck Jobs in the Queue System**
Thoughts: This is about calculating and displaying stuck job counts. We can test this by creating jobs with various created_at timestamps.
Testable: yes - property

**4.4 WHEN one or more Stuck Jobs exist THEN the Dashboard SHALL display a warning indicating the Queue Worker may not be running**
Thoughts: This is about conditional warning display based on stuck job count. We can test this with different stuck job counts.
Testable: yes - property

**4.5 WHEN displaying Queue Diagnostics THEN the Dashboard SHALL provide instructions for resolving common queue issues**
Thoughts: This is about ensuring specific content is present in the UI. We can test this by rendering diagnostics and checking for instruction text.
Testable: yes - property

**5.1 WHILE a user is viewing the Dashboard THEN the Dashboard SHALL poll for monitor status updates every 30 seconds**
Thoughts: This is about JavaScript polling behavior. We can test this by monitoring network requests over time.
Testable: yes - property

**5.2 WHEN a monitor status changes THEN the Dashboard SHALL update the display without a full page reload**
Thoughts: This is about UI update behavior. We can test this by simulating status changes and verifying DOM updates without navigation.
Testable: yes - property

**5.3 WHEN a monitor last_checked_at timestamp changes THEN the Dashboard SHALL display the updated timestamp**
Thoughts: This is about UI reflecting data changes. We can test this by updating timestamps and verifying display updates.
Testable: yes - property

**5.4 WHEN the Dashboard updates monitor data THEN the Dashboard SHALL preserve the user scroll position**
Thoughts: This is about scroll position preservation during updates. We can test this by setting scroll position, triggering updates, and verifying position.
Testable: yes - property

**5.5 IF a network error occurs during polling THEN the Dashboard SHALL continue polling attempts without disrupting the user interface**
Thoughts: This is about error handling behavior. We can test this by simulating network errors and verifying continued polling.
Testable: yes - property

**6.1 WHEN an administrator views the queue status on the Dashboard THEN the Dashboard SHALL display whether the Queue Worker is running**
Thoughts: This is about displaying worker status. We can test this with different worker states.
Testable: yes - property

**6.2 WHEN an administrator views the queue status on the Dashboard THEN the Dashboard SHALL display whether the Scheduler is running**
Thoughts: This is duplicate of 2.1, already covered.
Testable: yes - property (redundant)

**6.3 WHEN the Queue Worker is not running THEN the Dashboard SHALL display setup instructions for starting the Queue Worker**
Thoughts: This is about conditional instruction display. We can test this with worker in stopped state.
Testable: yes - property

**6.4 WHEN the Scheduler is not running THEN the Dashboard SHALL display setup instructions for starting the Scheduler**
Thoughts: This is duplicate of 2.2, already covered.
Testable: yes - property (redundant)

**6.5 WHEN both the Queue Worker and Scheduler are running THEN the Dashboard SHALL display a confirmation that the system is properly configured**
Thoughts: This is about displaying confirmation when both components are active. We can test this with both components running.
Testable: yes - property

**7.1-7.5**: These are documentation requirements, not functional requirements.
Testable: no

### Property Reflection

After reviewing all testable properties, I've identified the following redundancies:

1. **Property 6.2 is redundant with Property 2.1** - Both test scheduler status display
2. **Property 6.4 is redundant with Property 2.2** - Both test scheduler warning display

These redundant properties will be consolidated into the existing properties from Requirement 2.

### Correctness Properties

**Property 1: Test job dispatch**
*For any* user action triggering the "Test Queue" button, the Application should dispatch exactly one TestQueueJob to the Queue System
**Validates: Requirements 1.1**

**Property 2: Test job feedback**
*For any* Test Job dispatch action, the Dashboard should return a response containing a success message and log checking instructions
**Validates: Requirements 1.2**

**Property 3: Test job logging**
*For any* Test Job processed by the Queue Worker, the Application should write a log entry indicating successful queue processing
**Validates: Requirements 1.3**

**Property 4: Job persistence without worker**
*For any* Test Job dispatched when the Queue Worker is not running, the job should remain in the jobs table with a null reserved_at timestamp
**Validates: Requirements 1.4**

**Property 5: Scheduler status display**
*For any* Dashboard render, the queue status widget should display the current Scheduler running state (true or false)
**Validates: Requirements 2.1, 6.2**

**Property 6: Scheduler warning display**
*For any* Dashboard render when the Scheduler is not running, the queue status widget should contain a warning message and scheduler start instructions
**Validates: Requirements 2.2, 6.4**

**Property 7: Scheduler success display**
*For any* Dashboard render when the Scheduler is running, the queue status widget should contain a success message confirming automatic checks are enabled
**Validates: Requirements 2.3**

**Property 8: Stale monitor warning**
*For any* monitor where (current_time - last_checked_at) > check_interval_minutes AND status is pending, the Dashboard should display a warning that the Scheduler may not be running
**Validates: Requirements 2.4**

**Property 9: Scheduler command display**
*For any* Dashboard render displaying Scheduler status, the output should contain the command string needed to start the Scheduler
**Validates: Requirements 2.5**

**Property 10: Manual check dispatch**
*For any* monitor and user action triggering "Check Now", the Application should dispatch exactly one PerformMonitorCheck job for that monitor to the Queue System
**Validates: Requirements 3.1**

**Property 11: Manual check feedback**
*For any* manual check dispatch action, the Application should return a response containing a confirmation message
**Validates: Requirements 3.2**

**Property 12: Check completion updates**
*For any* Monitor Check Job that completes, the monitor's status and last_checked_at fields should be updated in the database
**Validates: Requirements 3.3**

**Property 13: Check now button presence**
*For any* monitor in the monitors list view, the rendered HTML should contain a "Check Now" action element for that monitor
**Validates: Requirements 3.5**

**Property 14: Pending jobs count accuracy**
*For any* queue state, the displayed pending jobs count should equal the number of records in the jobs table with null reserved_at
**Validates: Requirements 4.1**

**Property 15: Failed jobs count accuracy**
*For any* queue state, the displayed failed jobs count should equal the number of records in the failed_jobs table where failed_at is within the last 60 minutes
**Validates: Requirements 4.2**

**Property 16: Stuck jobs count accuracy**
*For any* queue state, the displayed stuck jobs count should equal the number of records in the jobs table where (current_time - created_at) > 5 minutes AND reserved_at is null
**Validates: Requirements 4.3**

**Property 17: Stuck jobs warning**
*For any* Dashboard render where stuck jobs count > 0, the queue status widget should display a warning indicating the Queue Worker may not be running
**Validates: Requirements 4.4**

**Property 18: Queue diagnostics instructions**
*For any* Dashboard render displaying Queue Diagnostics, the output should contain instructions for resolving common queue issues
**Validates: Requirements 4.5**

**Property 19: Dashboard polling interval**
*For any* Dashboard page load, the JavaScript should initiate HTTP requests to the monitor status endpoint at 30-second intervals
**Validates: Requirements 5.1**

**Property 20: Status update without reload**
*For any* monitor status change detected during polling, the Dashboard should update the DOM elements without triggering a page navigation event
**Validates: Requirements 5.2**

**Property 21: Timestamp update display**
*For any* monitor last_checked_at change detected during polling, the Dashboard should update the displayed timestamp in the DOM
**Validates: Requirements 5.3**

**Property 22: Scroll position preservation**
*For any* Dashboard update triggered by polling, the window scroll position should remain unchanged before and after the update
**Validates: Requirements 5.4**

**Property 23: Polling error resilience**
*For any* network error during a polling request, the Dashboard should continue making subsequent polling requests at the configured interval
**Validates: Requirements 5.5**

**Property 24: Queue worker status display**
*For any* Dashboard render, the queue status widget should display the current Queue Worker running state (true or false)
**Validates: Requirements 6.1**

**Property 25: Queue worker instructions display**
*For any* Dashboard render when the Queue Worker is not running, the queue status widget should contain setup instructions for starting the Queue Worker
**Validates: Requirements 6.3**

**Property 26: System configured confirmation**
*For any* Dashboard render when both Queue Worker and Scheduler are running, the queue status widget should display a confirmation message that the system is properly configured
**Validates: Requirements 6.5**

## Error Handling

### Queue Worker Not Running

**Detection**: 
- Stuck jobs count > 0 (jobs pending > 5 minutes)
- No recent job processing activity

**User Feedback**:
- Warning badge on queue status widget
- Clear message: "Queue Worker is not running"
- Instructions: "Start the queue worker with: `ddev artisan queue:work --tries=1`"
- Link to documentation

**System Behavior**:
- Jobs accumulate in jobs table
- Manual checks and test jobs remain pending
- No impact on application stability

### Scheduler Not Running

**Detection**:
- Cache key `scheduler:heartbeat` is missing or expired (> 90 seconds old)
- Monitors in pending status beyond their check interval

**User Feedback**:
- Warning badge on queue status widget
- Clear message: "Scheduler is not running"
- Instructions: "Start the scheduler with: `ddev artisan schedule:work`"
- Link to documentation

**System Behavior**:
- Automatic monitor checks not scheduled
- Manual checks still work
- No impact on application stability

### Network Errors During Polling

**Detection**:
- Fetch API returns error
- HTTP status code >= 400
- Network timeout

**User Feedback**:
- No visible error to user (silent failure)
- Console log for debugging

**System Behavior**:
- Continue polling at regular intervals
- Retry on next poll cycle
- No disruption to user experience

### Job Failures

**Detection**:
- Job throws exception
- Job exceeds max attempts

**User Feedback**:
- Failed jobs count increases
- Visible in queue diagnostics

**System Behavior**:
- Job moved to failed_jobs table
- Error logged with full stack trace
- Next scheduled check proceeds normally

### Invalid Monitor State

**Detection**:
- Monitor not found
- User lacks authorization

**User Feedback**:
- Error message on manual check attempt
- Redirect to monitors list

**System Behavior**:
- No job dispatched
- Flash error message
- Log security event

## Testing Strategy

### Unit Testing

**QueueDiagnosticsService Tests**:
- Test `getPendingJobsCount()` with various job states
- Test `getFailedJobsCount()` with various timestamps
- Test `getStuckJobsCount()` with various created_at values
- Test `isQueueWorkerRunning()` logic
- Test `isSchedulerRunning()` with cache states
- Mock database queries for isolation

**Controller Tests**:
- Test `testQueue()` dispatches job and returns redirect
- Test `triggerCheck()` dispatches job for authorized user
- Test `triggerCheck()` denies unauthorized access
- Test `status()` returns correct JSON structure
- Mock service layer for isolation

**JavaScript Tests**:
- Test polling initiates at correct interval
- Test DOM updates on status change
- Test scroll position preservation
- Test error handling continues polling
- Use Jest or similar framework

### Property-Based Testing

This project will use **PHPUnit with custom property testing helpers** for property-based testing, as Laravel projects typically use PHPUnit and there isn't a mature standalone PBT library for PHP. We'll implement a simple property testing framework using PHPUnit's data providers with randomized inputs.

**Configuration**:
- Each property-based test will run a minimum of 100 iterations
- Use PHPUnit data providers to generate random test cases
- Seed random generation for reproducibility

**Property Test Implementation Pattern**:
```php
/**
 * @test
 * @dataProvider randomMonitorStatesProvider
 * Feature: queue-diagnostics, Property 8: Stale monitor warning
 */
public function stale_monitors_trigger_scheduler_warning($monitor_data): void
{
    // Test implementation
}

public function randomMonitorStatesProvider(): array
{
    $cases = [];
    for ($i = 0; $i < 100; $i++) {
        $cases[] = [/* random monitor data */];
    }
    return $cases;
}
```

**Property Tests to Implement**:

1. **Property 1: Test job dispatch** - Generate random user sessions, trigger test queue, verify job in database
2. **Property 3: Test job logging** - Process random test jobs, verify log entries exist
3. **Property 4: Job persistence** - Dispatch random jobs without worker, verify jobs table state
4. **Property 8: Stale monitor warning** - Generate monitors with random intervals and timestamps, verify warning logic
5. **Property 10: Manual check dispatch** - Generate random monitors, trigger checks, verify job dispatch
6. **Property 12: Check completion updates** - Process random check jobs, verify database updates
7. **Property 13: Check now button presence** - Render lists with random monitor counts, verify button presence
8. **Property 14-16: Count accuracy** - Generate random queue states, verify count calculations
9. **Property 17: Stuck jobs warning** - Generate random stuck job counts, verify warning display
10. **Property 19: Polling interval** - Monitor network requests over random time periods
11. **Property 20-23: Dashboard updates** - Simulate random status changes, verify UI behavior

### Integration Testing

**End-to-End Flows**:
- Test complete test queue flow (dispatch → process → log)
- Test complete manual check flow (trigger → queue → process → update)
- Test dashboard auto-refresh with real polling
- Test queue diagnostics with real database state

**Database Integration**:
- Use SQLite in-memory database for speed
- Seed realistic test data
- Verify database state after operations

### Feature Testing

**HTTP Tests**:
- Test all routes return correct status codes
- Test authentication and authorization
- Test CSRF protection
- Test JSON API responses

**Browser Tests** (Optional):
- Use Laravel Dusk for JavaScript testing
- Test auto-refresh behavior
- Test scroll position preservation
- Test button interactions

## Implementation Notes

### Scheduler Heartbeat Mechanism

The scheduler status detection uses a cache-based heartbeat:

1. Create a scheduled command that runs every minute
2. Command updates cache key `scheduler:heartbeat` with current timestamp
3. TTL set to 90 seconds (allows for one missed beat)
4. Service checks if key exists and is recent (< 90 seconds old)

**Command Implementation**:
```php
// app/Console/Commands/SchedulerHeartbeat.php
protected function handle(): void
{
    Cache::put('scheduler:heartbeat', now()->timestamp, 90);
}
```

**Schedule Registration**:
```php
// app/Console/Kernel.php or routes/console.php
Schedule::command('scheduler:heartbeat')->everyMinute();
```

### Queue Worker Detection Logic

Queue worker status is inferred from stuck jobs:

- If stuck jobs count > 0: Worker likely not running
- If stuck jobs count === 0: Worker likely running (or no jobs queued)

This is a heuristic, not a definitive check, but provides useful feedback to users.

### Dashboard Auto-Refresh Implementation

**Polling Strategy**:
- Use `setInterval()` for consistent 30-second polling
- Use `fetch()` API with error handling
- Update only changed elements to minimize DOM manipulation
- Store current scroll position before update
- Restore scroll position after update

**Performance Considerations**:
- Only poll when tab is visible (use Page Visibility API)
- Debounce rapid updates
- Minimize payload size (only return necessary data)

### DDEV Integration

All commands in documentation must use `ddev` prefix:
- `ddev artisan queue:work --tries=1`
- `ddev artisan schedule:work`
- `ddev composer dev` (includes queue worker)

### Security Considerations

**Authorization**:
- Manual check requires monitor ownership
- Queue diagnostics visible to all authenticated users
- Test queue available to all authenticated users

**Rate Limiting**:
- Apply rate limiting to manual check endpoint
- Prevent abuse of test queue endpoint
- Limit polling frequency client-side

**Input Validation**:
- Validate monitor ID in manual check requests
- Sanitize all user inputs
- Use Laravel's built-in CSRF protection

## Documentation Requirements

### User Documentation

**Location**: `.docs/queue-testing.md` (existing file to be enhanced)

**Content**:
- How to start queue worker in development
- How to start scheduler in development
- How to use "Test Queue" button
- How to use "Check Now" button
- How to interpret queue diagnostics
- Troubleshooting common issues

### Developer Documentation

**Location**: `.docs/deployment.md` (existing file to be enhanced)

**Content**:
- Production queue worker setup (supervisor/systemd)
- Production scheduler setup (cron)
- Monitoring queue health in production
- Scaling queue workers
- Handling failed jobs

### Setup Documentation

**Location**: `.docs/setup.md` (existing file to be enhanced)

**Content**:
- Add queue worker to setup checklist
- Add scheduler to setup checklist
- Verify queue functionality after setup
- Common setup issues and solutions
