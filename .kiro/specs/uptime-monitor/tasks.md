# Implementation Plan

- [x] 1. Set up authentication and user management
  - Configure Laravel Breeze or implement custom authentication
  - Create login, logout, and session management
  - Add authentication middleware to protect dashboard routes
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 1.1 Write property test for authentication protection
  - **Property 1: Authentication protects dashboard access**
  - **Validates: Requirements 1.1**

- [x] 1.2 Write property test for valid credential authentication
  - **Property 2: Valid credentials grant access**
  - **Validates: Requirements 1.2**

- [x] 1.3 Write property test for invalid credential rejection
  - **Property 3: Invalid credentials are rejected**
  - **Validates: Requirements 1.3**

- [x] 2. Create database schema and migrations
  - Create monitors table migration with all required fields
  - Create checks table migration with foreign key to monitors
  - Create notification_settings table migration
  - Add indexes for performance optimization
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 3. Implement core models and relationships
  - Create Monitor model with fillable fields, casts, and relationships
  - Create Check model with fillable fields, casts, and relationships
  - Create NotificationSettings model
  - Add helper methods (isUp(), isDown(), isPending(), wasSuccessful(), wasFailed())
  - _Requirements: 2.1, 6.2, 6.3_

- [x] 3.1 Write unit tests for model relationships
  - Test User → Monitors relationship
  - Test Monitor → Checks relationship
  - Test Monitor → User relationship
  - _Requirements: 2.1, 6.5_

- [x] 3.2 Write unit tests for model helper methods
  - Test isUp(), isDown(), isPending() methods
  - Test wasSuccessful(), wasFailed() methods
  - _Requirements: 6.2, 6.3_

- [x] 4. Create form request validation classes
  - Create StoreMonitorRequest with validation rules for name, URL, and check interval
  - Create UpdateMonitorRequest with validation rules
  - Add custom error messages for validation failures
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 4.1, 4.5_

- [x] 4.1 Write property test for monitor creation with valid URL
  - **Property 4: Monitor creation with valid URL succeeds**
  - **Validates: Requirements 2.1**

- [x] 4.2 Write property test for monitor creation with invalid URL
  - **Property 5: Monitor creation with invalid URL fails**
  - **Validates: Requirements 2.2**

- [x] 5. Implement MonitorService for business logic
  - Create MonitorService class with dependency injection
  - Implement createMonitor() method to create monitors with pending status
  - Implement updateMonitor() method to update monitor details
  - Implement deleteMonitor() method to remove monitors
  - Implement getAllMonitorsForUser() method to fetch user's monitors
  - Implement getMonitorWithStats() method to fetch monitor with uptime statistics
  - _Requirements: 2.1, 2.5, 3.1, 4.2, 4.3, 4.4, 5.1, 11.1, 11.2, 11.3_

- [ ]* 5.1 Write property test for monitor updates preserving history
  - **Property 7: Monitor updates preserve history**
  - **Validates: Requirements 4.3**

- [ ]* 5.2 Write property test for monitor deletion removing all data
  - **Property 8: Monitor deletion removes all data**
  - **Validates: Requirements 5.1**

- [ ]* 5.3 Write property test for new monitors having pending status
  - **Property 4: Monitor creation with valid URL succeeds** (verify pending status)
  - **Validates: Requirements 2.5**

- [x] 6. Implement CheckService for HTTP monitoring
  - Create CheckService class with HTTP client dependency
  - Implement performCheck() method to execute HTTP requests with 30-second timeout
  - Implement status code evaluation logic (2xx = success, 4xx/5xx = failure)
  - Implement timeout handling and error message recording
  - Implement status change detection and notification triggering
  - Implement calculateUptime() method for uptime percentage calculation
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 7.1, 7.2, 7.3, 7.4, 7.5, 11.4_

- [x] 6.1 Write property test for successful HTTP responses marking monitors as up
  - **Property 9: Successful HTTP responses mark monitors as up**
  - **Validates: Requirements 6.2**

- [x] 6.2 Write property test for failed HTTP responses marking monitors as down
  - **Property 10: Failed HTTP responses mark monitors as down**
  - **Validates: Requirements 6.3**

- [x] 6.3 Write property test for timeout failures marking monitors as down
  - **Property 11: Timeout failures mark monitors as down**
  - **Validates: Requirements 6.4**

- [x] 6.4 Write property test for status transitions triggering notifications
  - **Property 12: Status transitions trigger notifications**
  - **Validates: Requirements 7.1, 7.2**

- [x] 6.5 Write property test for stable status preventing duplicate notifications
  - **Property 13: Stable status prevents duplicate notifications**
  - **Validates: Requirements 7.3, 7.4**

- [x] 6.6 Write property test for first check not triggering notification
  - **Property 14: First check does not trigger notification**
  - **Validates: Requirements 7.5**

- [x] 6.7 Write property test for uptime calculation accuracy
  - **Property 17: Uptime calculation accuracy**
  - **Validates: Requirements 11.4**

- [x] 7. Create queue job for performing checks
  - Create PerformMonitorCheck job class implementing ShouldQueue
  - Inject CheckService into job handle method
  - Implement job execution logic to call CheckService
  - Add error handling and logging for job failures
  - _Requirements: 6.1, 12.1, 12.2, 12.4_

- [x] 7.1 Write property test for checks executing asynchronously
  - **Property 18: Checks execute asynchronously**
  - **Validates: Requirements 12.1, 12.2**

- [x] 8. Implement scheduler for periodic checks
  - Create ScheduleMonitorChecks command to dispatch check jobs
  - Register command in Laravel scheduler to run every minute
  - Implement logic to find monitors due for checking based on check_interval_minutes
  - Dispatch PerformMonitorCheck job for each due monitor
  - _Requirements: 6.1, 12.1, 12.3_

- [x] 9. Implement NotificationService for alerts
  - Create NotificationService class with mail and HTTP client dependencies
  - Implement notifyStatusChange() method to route to appropriate channels
  - Implement sendEmailNotification() method with monitor details, status, and timestamp
  - Implement sendPushoverNotification() method with Pushover API integration
  - Add priority logic (high for down, normal for recovery)
  - Add error handling to prevent notification failures from blocking checks
  - _Requirements: 7.1, 7.2, 8.1, 8.2, 8.3, 8.4, 8.5, 9.1, 9.2, 9.3, 9.4, 9.5_

- [x] 9.1 Write property test for email notifications including required details
  - **Property 15: Email notifications include required details**
  - **Validates: Requirements 8.2**

- [x] 9.2 Write property test for Pushover notifications using correct priority
  - **Property 16: Pushover notifications use correct priority**
  - **Validates: Requirements 9.4, 9.5**

- [x] 10. Create notification settings management
  - Create NotificationSettings model and migration
  - Create form for configuring email and Pushover settings
  - Create controller methods for updating notification settings
  - Validate and encrypt Pushover API tokens before storage
  - _Requirements: 8.1, 9.1, 9.3_

- [x] 11. Implement MonitorController for CRUD operations
  - Create MonitorController with resource methods
  - Implement index() to display all monitors for authenticated user
  - Implement create() to show monitor creation form
  - Implement store() to create new monitor using StoreMonitorRequest
  - Implement show() to display monitor details with check history
  - Implement edit() to show monitor edit form
  - Implement update() to update monitor using UpdateMonitorRequest
  - Implement destroy() to delete monitor with confirmation
  - Add authorization policies to ensure users can only access their own monitors
  - _Requirements: 2.1, 2.2, 3.1, 4.1, 4.2, 4.4, 4.5, 5.1, 5.2, 5.4, 5.5, 10.1_

- [x] 11.1 Write property test for dashboard displaying all user monitors
  - **Property 6: Dashboard displays all user monitors**
  - **Validates: Requirements 3.1**

- [x] 12. Implement DashboardController
  - Create DashboardController with index method
  - Fetch all monitors for authenticated user with current status
  - Order monitors by status priority (down, up, pending)
  - Pass monitors to dashboard view
  - _Requirements: 3.1, 3.5_

- [x] 13. Create Blade views for authentication
  - Create login view with email and password fields
  - Add CSRF protection to login form
  - Style with Tailwind CSS for clean, modern appearance
  - Add validation error display
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 14. Create Blade views for dashboard
  - Create dashboard layout with header, navigation, and footer
  - Create dashboard index view displaying monitor cards
  - Show monitor name, URL, status, last check time, and response time
  - Add visual distinction for down monitors (red styling)
  - Implement status-based ordering display
  - Add "Add Monitor" button linking to create form
  - Style with Tailwind CSS
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 15. Create Blade views for monitor management
  - Create monitor create form with name, URL, and check interval fields
  - Create monitor edit form pre-populated with current values
  - Create monitor show view with details, statistics, and check history
  - Add delete button with Alpine.js confirmation dialog
  - Display uptime percentages for 24h, 7d, and 30d periods
  - Display paginated check history table
  - Style all forms and views with Tailwind CSS
  - _Requirements: 2.1, 2.3, 2.4, 4.1, 5.3, 10.1, 10.2, 10.3, 10.4, 10.5, 11.1, 11.2, 11.3_

- [x] 16. Create email notification templates
  - Create Blade template for down notification email
  - Create Blade template for recovery notification email
  - Include monitor name, URL, status change, timestamp in both templates
  - Include error details/status code in down notification
  - Include downtime duration in recovery notification
  - Style emails with inline CSS for email client compatibility
  - _Requirements: 8.2, 8.3, 8.4_

- [x] 17. Configure routes and middleware
  - Define authentication routes (login, logout)
  - Define dashboard route with auth middleware
  - Define monitor resource routes with auth middleware
  - Define notification settings routes with auth middleware
  - Add route model binding for Monitor model
  - _Requirements: 1.1, 1.4, 1.5, 3.1_

- [x] 18. Set up environment configuration
  - Add MAIL_* variables to .env.example for email configuration
  - Add PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN to .env.example
  - Add CHECK_TIMEOUT variable (default 30 seconds)
  - Document all environment variables in .env.example comments
  - _Requirements: 8.1, 9.1, 9.3_

- [x] 19. Implement monitor authorization policies
  - Create MonitorPolicy class
  - Implement view, update, and delete policy methods
  - Ensure users can only access their own monitors
  - Register policy in AuthServiceProvider
  - _Requirements: 3.1, 4.1, 5.1_

- [x] 20. Add Alpine.js for interactive elements
  - Include Alpine.js via CDN or npm
  - Implement delete confirmation dialog using Alpine.js
  - Add real-time status indicator updates (optional enhancement)
  - Keep JavaScript minimal and progressive enhancement focused
  - _Requirements: 5.3_

- [x] 21. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 22. Create user documentation
  - Create documentation directory structure
  - Write setup.md with installation instructions
  - Document environment variable configuration
  - Write usage.md with monitor management instructions
  - Document email and Pushover notification setup
  - Include screenshots or examples where helpful
  - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

- [x] 23. Create database seeders for development
  - Create UserSeeder to create test admin user
  - Create MonitorSeeder to create sample monitors
  - Create CheckSeeder to create sample check history
  - Update DatabaseSeeder to call all seeders
  - _Requirements: Development support_

- [x] 24. Configure queue worker for production
  - Document queue worker setup in documentation/deployment.md
  - Document Supervisor configuration for queue worker
  - Document Laravel scheduler cron configuration
  - Add queue monitoring recommendations
  - _Requirements: 12.1, 12.5_

- [x] 25. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
