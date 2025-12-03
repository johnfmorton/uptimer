# Implementation Plan

- [x] 1. Create SendTestNotification job
  - Create queued job class that accepts user and channel (email/pushover)
  - Implement handle method to call NotificationService based on channel
  - Add comprehensive error handling and logging
  - Log user ID for audit trail
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 6.5_

- [ ]* 1.1 Write property test for job dispatch
  - **Property 2: Test notification triggers job dispatch**
  - **Validates: Requirements 1.2, 2.2, 4.1**

- [ ]* 1.2 Write property test for success logging
  - **Property 9: Successful job execution is logged**
  - **Validates: Requirements 4.3**

- [ ]* 1.3 Write property test for error logging
  - **Property 10: Failed job execution is logged with details**
  - **Validates: Requirements 4.4**

- [ ]* 1.4 Write property test for user context logging
  - **Property 13: Test notification execution is logged with user context**
  - **Validates: Requirements 6.5**

- [x] 2. Add test notification methods to NotificationService
  - Implement sendTestEmail() method that reuses existing email sending logic
  - Implement sendTestPushover() method that reuses existing Pushover logic
  - Use test-specific content (subject, title, message)
  - Set Pushover priority to 0 (normal)
  - Ensure methods use user's NotificationSettings credentials
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 5.1, 5.5_

- [ ]* 2.1 Write property test for email content markers
  - **Property 5: Test email contains identifying markers**
  - **Validates: Requirements 3.1, 3.2**

- [ ]* 2.2 Write property test for Pushover content markers
  - **Property 6: Test Pushover contains identifying markers**
  - **Validates: Requirements 3.3, 3.4**

- [ ]* 2.3 Write property test for Pushover priority
  - **Property 7: Test Pushover uses normal priority**
  - **Validates: Requirements 3.5**

- [ ]* 2.4 Write property test for credential usage
  - **Property 11: Test notifications use user's credentials**
  - **Validates: Requirements 5.5, 6.1**

- [x] 3. Create test notification email template
  - Create resources/views/emails/test-notification.blade.php
  - Include clear "Test Notification" heading
  - Add explanation that this is a test message
  - Include timestamp
  - Add link back to notification settings
  - Reuse existing email layout styling
  - _Requirements: 3.1, 3.2_

- [x] 4. Add controller methods for test notifications
  - Add testEmail() method to NotificationSettingsController
  - Add testPushover() method to NotificationSettingsController
  - Validate notification channel is enabled before dispatching
  - Dispatch SendTestNotification job
  - Return immediate redirect with success message
  - Handle errors with appropriate error messages
  - _Requirements: 1.2, 1.3, 1.4, 2.2, 2.3, 2.4, 4.2, 6.1, 6.2_

- [ ]* 4.1 Write property test for success feedback
  - **Property 3: Successful test returns success feedback**
  - **Validates: Requirements 1.3, 2.3**

- [ ]* 4.2 Write property test for error feedback
  - **Property 4: Failed test returns error feedback**
  - **Validates: Requirements 1.4, 2.4**

- [ ]* 4.3 Write property test for immediate response
  - **Property 8: Job execution returns immediate response**
  - **Validates: Requirements 4.2**

- [ ]* 4.4 Write property test for authentication requirement
  - **Property 12: Unauthenticated requests are rejected**
  - **Validates: Requirements 6.3**

- [x] 5. Add routes for test notifications
  - Add POST route for /notification-settings/test-email
  - Add POST route for /notification-settings/test-pushover
  - Ensure routes are within auth middleware group
  - Name routes appropriately for route() helper
  - _Requirements: 6.3_

- [x] 6. Update notification settings view with test buttons
  - Add "Send Test Email" button in email notifications section
  - Add "Send Test Pushover" button in Pushover notifications section
  - Show buttons only when respective channel is enabled
  - Use secondary button styling
  - Include CSRF token in forms
  - Add proper form actions pointing to test routes
  - _Requirements: 1.1, 1.5, 2.1, 2.5_

- [ ]* 6.1 Write property test for button visibility
  - **Property 1: Test button visibility matches notification enabled state**
  - **Validates: Requirements 1.1, 2.1**

- [x] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
