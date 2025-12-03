# Implementation Plan

- [x] 1. Create QueueDiagnosticsService with core diagnostic methods
  - Implement service class in `app/Services/QueueDiagnosticsService.php`
  - Implement `getPendingJobsCount()` to query jobs table
  - Implement `getFailedJobsCount()` with 60-minute time filter
  - Implement `getStuckJobsCount()` for jobs pending > 5 minutes
  - Implement `isQueueWorkerRunning()` using stuck jobs heuristic
  - Implement `isSchedulerRunning()` using cache heartbeat check
  - Implement `getQueueDiagnostics()` to return comprehensive diagnostics array
  - Implement `dispatchTestJob()` to dispatch TestQueueJob
  - _Requirements: 1.1, 1.3, 1.4, 2.1, 4.1, 4.2, 4.3, 6.1, 6.2_

- [ ]* 1.1 Write property test for pending jobs count accuracy
  - **Property 14: Pending jobs count accuracy**
  - **Validates: Requirements 4.1**

- [ ]* 1.2 Write property test for failed jobs count accuracy
  - **Property 15: Failed jobs count accuracy**
  - **Validates: Requirements 4.2**

- [ ]* 1.3 Write property test for stuck jobs count accuracy
  - **Property 16: Stuck jobs count accuracy**
  - **Validates: Requirements 4.3**

- [ ]* 1.4 Write unit tests for QueueDiagnosticsService methods
  - Test `getPendingJobsCount()` with various job states
  - Test `getFailedJobsCount()` with various timestamps
  - Test `getStuckJobsCount()` with various created_at values
  - Test `isQueueWorkerRunning()` logic
  - Test `isSchedulerRunning()` with cache states
  - _Requirements: 1.1, 1.3, 1.4, 2.1, 4.1, 4.2, 4.3, 6.1, 6.2_

- [x] 2. Create scheduler heartbeat command
  - Create `app/Console/Commands/SchedulerHeartbeat.php` command
  - Implement handle method to update cache key `scheduler:heartbeat` with current timestamp
  - Set cache TTL to 90 seconds
  - Register command in `routes/console.php` to run every minute
  - _Requirements: 2.1, 6.2_

- [x] 2.1 Write unit test for scheduler heartbeat command
  - Test command updates cache with correct key and TTL
  - Test cache value is current timestamp
  - _Requirements: 2.1, 6.2_

- [x] 3. Create QueueDiagnosticsController
  - Create controller in `app/Http/Controllers/QueueDiagnosticsController.php`
  - Inject QueueDiagnosticsService via constructor
  - Implement `testQueue()` method to dispatch test job and return redirect with success message
  - Implement `status()` method to return queue diagnostics as JSON
  - Add routes: `POST /queue/test` and `GET /api/queue/status`
  - Apply authentication middleware to both routes
  - _Requirements: 1.1, 1.2, 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ]* 3.1 Write property test for test job dispatch
  - **Property 1: Test job dispatch**
  - **Validates: Requirements 1.1**

- [ ]* 3.2 Write property test for test job feedback
  - **Property 2: Test job feedback**
  - **Validates: Requirements 1.2**

- [ ]* 3.3 Write feature tests for QueueDiagnosticsController
  - Test `testQueue()` dispatches job and returns redirect
  - Test `testQueue()` requires authentication
  - Test `status()` returns correct JSON structure
  - Test `status()` requires authentication
  - _Requirements: 1.1, 1.2, 4.1, 4.2, 4.3_

- [x] 4. Enhance MonitorController with manual check capability
  - Add `triggerCheck()` method to `app/Http/Controllers/MonitorController.php`
  - Inject authorization check using MonitorPolicy
  - Dispatch PerformMonitorCheck job for the monitor
  - Return redirect with confirmation message
  - Add route: `POST /monitors/{monitor}/check`
  - Apply authentication middleware
  - _Requirements: 3.1, 3.2_

- [x] 4.1 Write property test for manual check dispatch
  - **Property 10: Manual check dispatch**
  - **Validates: Requirements 3.1**

- [ ]* 4.2 Write property test for manual check feedback
  - **Property 11: Manual check feedback**
  - **Validates: Requirements 3.2**

- [ ]* 4.3 Write property test for check completion updates
  - **Property 12: Check completion updates**
  - **Validates: Requirements 3.3**

- [ ]* 4.4 Write feature tests for manual check functionality
  - Test `triggerCheck()` dispatches job for authorized user
  - Test `triggerCheck()` denies unauthorized access
  - Test `triggerCheck()` returns confirmation message
  - _Requirements: 3.1, 3.2_

- [x] 5. Enhance DashboardController with queue diagnostics
  - Inject QueueDiagnosticsService into `app/Http/Controllers/DashboardController.php`
  - Call `getQueueDiagnostics()` in `index()` method
  - Pass diagnostics data to dashboard view
  - _Requirements: 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 4.1, 4.2, 4.3, 4.4, 4.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ]* 5.1 Write feature test for dashboard with diagnostics
  - Test dashboard displays queue diagnostics
  - Test dashboard passes diagnostics to view
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 6. Create queue status Blade component
  - Create `resources/views/components/queue-status.blade.php`
  - Accept `$diagnostics` prop
  - Display queue worker status with icon (running/not running)
  - Display scheduler status with icon (running/not running)
  - Display pending jobs count
  - Display failed jobs count (last hour)
  - Display stuck jobs count with warning badge if > 0
  - Display warning message when queue worker not running
  - Display warning message when scheduler not running
  - Display success message when both running
  - Display setup instructions for queue worker when not running
  - Display setup instructions for scheduler when not running
  - Display confirmation when system properly configured
  - Include "Test Queue" button with POST form to `/queue/test`
  - Use Tailwind CSS for styling consistent with existing components
  - _Requirements: 1.5, 2.1, 2.2, 2.3, 2.5, 4.1, 4.2, 4.3, 4.4, 4.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ]* 6.1 Write property test for scheduler status display
  - **Property 5: Scheduler status display**
  - **Validates: Requirements 2.1, 6.2**

- [ ]* 6.2 Write property test for scheduler warning display
  - **Property 6: Scheduler warning display**
  - **Validates: Requirements 2.2, 6.4**

- [ ]* 6.3 Write property test for scheduler success display
  - **Property 7: Scheduler success display**
  - **Validates: Requirements 2.3**

- [ ]* 6.4 Write property test for stale monitor warning
  - **Property 8: Stale monitor warning**
  - **Validates: Requirements 2.4**

- [ ]* 6.5 Write property test for scheduler command display
  - **Property 9: Scheduler command display**
  - **Validates: Requirements 2.5**

- [ ]* 6.6 Write property test for stuck jobs warning
  - **Property 17: Stuck jobs warning**
  - **Validates: Requirements 4.4**

- [ ]* 6.7 Write property test for queue diagnostics instructions
  - **Property 18: Queue diagnostics instructions**
  - **Validates: Requirements 4.5**

- [ ]* 6.8 Write property test for queue worker status display
  - **Property 24: Queue worker status display**
  - **Validates: Requirements 6.1**

- [ ]* 6.9 Write property test for queue worker instructions display
  - **Property 25: Queue worker instructions display**
  - **Validates: Requirements 6.3**

- [ ]* 6.10 Write property test for system configured confirmation
  - **Property 26: System configured confirmation**
  - **Validates: Requirements 6.5**

- [x] 7. Integrate queue status component into dashboard
  - Update `resources/views/dashboard.blade.php`
  - Add queue status component at top of page above monitors list
  - Pass `$diagnostics` data to component
  - Ensure component is prominently displayed
  - _Requirements: 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 4.1, 4.2, 4.3, 4.4, 4.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 8. Add "Check Now" buttons to monitor views
  - Update `resources/views/monitors/show.blade.php` to add "Check Now" button
  - Update `resources/views/monitors/index.blade.php` to add "Check Now" action for each monitor
  - Create POST form to `/monitors/{monitor}/check` with CSRF token
  - Style buttons consistent with existing UI
  - _Requirements: 3.4, 3.5_

- [ ]* 8.1 Write property test for check now button presence
  - **Property 13: Check now button presence**
  - **Validates: Requirements 3.5**

- [x] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Create dashboard auto-refresh JavaScript
  - Create `resources/js/dashboard-refresh.js`
  - Implement polling function using `setInterval()` for 30-second intervals
  - Use `fetch()` API to call `/api/monitors` endpoint
  - Parse JSON response and update monitor status badges in DOM
  - Update last_checked_at timestamps in DOM
  - Store scroll position before update using `window.scrollY`
  - Restore scroll position after update
  - Implement error handling to continue polling on network errors
  - Log errors to console for debugging
  - Use Page Visibility API to pause polling when tab not visible
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 10.1 Write property test for dashboard polling interval
  - **Property 19: Dashboard polling interval**
  - **Validates: Requirements 5.1**

- [ ]* 10.2 Write property test for status update without reload
  - **Property 20: Status update without reload**
  - **Validates: Requirements 5.2**

- [ ]* 10.3 Write property test for timestamp update display
  - **Property 21: Timestamp update display**
  - **Validates: Requirements 5.3**

- [ ]* 10.4 Write property test for scroll position preservation
  - **Property 22: Scroll position preservation**
  - **Validates: Requirements 5.4**

- [ ]* 10.5 Write property test for polling error resilience
  - **Property 23: Polling error resilience**
  - **Validates: Requirements 5.5**

- [x] 11. Create API endpoint for monitor status polling
  - Add `api()` method to `app/Http/Controllers/MonitorController.php`
  - Return JSON array of all user's monitors with status and last_checked_at
  - Add route: `GET /api/monitors`
  - Apply authentication middleware
  - Optimize query with only necessary fields
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 11.1 Write feature test for monitors API endpoint
  - Test endpoint returns correct JSON structure
  - Test endpoint requires authentication
  - Test endpoint returns only user's monitors
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 12. Integrate auto-refresh JavaScript into dashboard
  - Import `dashboard-refresh.js` in `resources/js/app.js`
  - Initialize auto-refresh on dashboard page load
  - Add data attributes to monitor elements for JavaScript targeting
  - Ensure JavaScript only runs on dashboard page
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 13. Update TestQueueJob to log clear success message
  - Enhance `app/Jobs/TestQueueJob.php` handle method
  - Ensure log message clearly indicates successful queue processing
  - Include timestamp and job details in log
  - _Requirements: 1.3_

- [ ]* 13.1 Write property test for test job logging
  - **Property 3: Test job logging**
  - **Validates: Requirements 1.3**

- [ ]* 13.2 Write property test for job persistence without worker
  - **Property 4: Job persistence without worker**
  - **Validates: Requirements 1.4**

- [x] 14. Enhance queue testing documentation
  - Update `.docs/queue-testing.md`
  - Add section on using "Test Queue" button
  - Add section on using "Check Now" button
  - Add section on interpreting queue diagnostics
  - Add troubleshooting guide for common issues
  - Include screenshots of queue status widget
  - _Requirements: 7.1, 7.2, 7.3_

- [ ] 15. Enhance deployment documentation
  - Update `.docs/deployment.md`
  - Add production queue worker setup instructions (supervisor/systemd)
  - Add production scheduler setup instructions (cron)
  - Add monitoring queue health in production
  - Add scaling queue workers guidance
  - Add handling failed jobs procedures
  - _Requirements: 7.5_

- [ ] 16. Enhance setup documentation
  - Update `.docs/setup.md`
  - Add queue worker to setup checklist
  - Add scheduler to setup checklist
  - Add verification steps for queue functionality
  - Add common setup issues and solutions
  - Emphasize `ddev composer dev` for development
  - _Requirements: 7.1, 7.2, 7.4_

- [ ] 17. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
