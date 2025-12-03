# Requirements Document

## Introduction

This feature enhances the queue and scheduler diagnostic capabilities of the uptime monitoring application. Currently, users cannot easily test if the queue system is working, and monitors remain in "pending" status because the Laravel scheduler is not running. This feature will provide clear feedback about queue and scheduler health, allow users to test the queue system, and enable manual monitor checks.

## Glossary

- **Queue System**: Laravel's job queue that processes background tasks asynchronously
- **Queue Worker**: The process (`queue:work`) that executes jobs from the queue
- **Scheduler**: Laravel's task scheduler that runs scheduled commands at defined intervals
- **Monitor Check Job**: A queued job that performs an HTTP check on a monitored URL
- **Test Job**: A simple job dispatched to verify queue functionality
- **Dashboard**: The main authenticated user interface showing monitors and system status
- **Application**: The uptime monitoring web application
- **Stuck Job**: A job that has been pending in the queue for more than 5 minutes
- **Queue Diagnostics**: System information about queue health including pending, failed, and stuck job counts

## Requirements

### Requirement 1

**User Story:** As a user, I want to test if the queue system is working, so that I can verify my background jobs will be processed.

#### Acceptance Criteria

1. WHEN a user clicks a "Test Queue" button on the Dashboard THEN the Application SHALL dispatch a Test Job to the Queue System
2. WHEN a Test Job is dispatched THEN the Dashboard SHALL display a success message with instructions to check logs
3. WHEN a Test Job is processed by the Queue Worker THEN the Application SHALL log a message indicating successful queue processing
4. WHEN the Queue Worker is not running THEN the Test Job SHALL remain in the jobs table with pending status
5. WHEN a user views the Dashboard THEN the Dashboard SHALL display a "Test Queue" button near the queue status indicator

### Requirement 2

**User Story:** As a user, I want to see if the Laravel scheduler is running, so that I know whether my monitors will be checked automatically.

#### Acceptance Criteria

1. WHEN a user views the queue status on the Dashboard THEN the Dashboard SHALL indicate whether the Scheduler is running
2. WHEN the Scheduler is not running THEN the Dashboard SHALL display a warning message with instructions to start the Scheduler
3. WHEN the Scheduler is running THEN the Dashboard SHALL display a success message confirming automatic checks are enabled
4. WHEN monitors remain in pending status for a duration exceeding their configured check interval THEN the Dashboard SHALL display a warning indicating the Scheduler may not be running
5. WHEN displaying Scheduler status THEN the Dashboard SHALL provide the command needed to start the Scheduler

### Requirement 3

**User Story:** As a user, I want to manually trigger a check for a specific monitor, so that I can immediately verify it's working without waiting for the scheduled check.

#### Acceptance Criteria

1. WHEN a user clicks "Check Now" on a monitor THEN the Application SHALL dispatch a Monitor Check Job for that monitor to the Queue System
2. WHEN a Monitor Check Job is dispatched THEN the Dashboard SHALL display a confirmation message
3. WHEN a Monitor Check Job completes THEN the Application SHALL update the monitor status and last_checked_at timestamp
4. WHEN a user views a monitor detail page THEN the Dashboard SHALL display a "Check Now" button
5. WHEN a user views the monitors list THEN the Dashboard SHALL display a "Check Now" action for each monitor

### Requirement 4

**User Story:** As a user, I want clear diagnostic information about queue health, so that I can troubleshoot issues when monitors aren't being checked.

#### Acceptance Criteria

1. WHEN a user views the queue status on the Dashboard THEN the Dashboard SHALL display the count of pending jobs in the Queue System
2. WHEN a user views the queue status on the Dashboard THEN the Dashboard SHALL display the count of failed jobs within the last 60 minutes
3. WHEN a user views the queue status on the Dashboard THEN the Dashboard SHALL display the count of Stuck Jobs in the Queue System
4. WHEN one or more Stuck Jobs exist THEN the Dashboard SHALL display a warning indicating the Queue Worker may not be running
5. WHEN displaying Queue Diagnostics THEN the Dashboard SHALL provide instructions for resolving common queue issues

### Requirement 5

**User Story:** As a user, I want the dashboard to automatically refresh monitor statuses, so that I can see updates without manually refreshing the page.

#### Acceptance Criteria

1. WHILE a user is viewing the Dashboard THEN the Dashboard SHALL poll for monitor status updates every 30 seconds
2. WHEN a monitor status changes THEN the Dashboard SHALL update the display without a full page reload
3. WHEN a monitor last_checked_at timestamp changes THEN the Dashboard SHALL display the updated timestamp
4. WHEN the Dashboard updates monitor data THEN the Dashboard SHALL preserve the user scroll position
5. IF a network error occurs during polling THEN the Dashboard SHALL continue polling attempts without disrupting the user interface

### Requirement 6

**User Story:** As an administrator, I want to check the queue status to understand if I have properly set up all required job queue and scheduling components on my server, so that I can verify the system is configured correctly.

#### Acceptance Criteria

1. WHEN an administrator views the queue status on the Dashboard THEN the Dashboard SHALL display whether the Queue Worker is running
2. WHEN an administrator views the queue status on the Dashboard THEN the Dashboard SHALL display whether the Scheduler is running
3. WHEN the Queue Worker is not running THEN the Dashboard SHALL display setup instructions for starting the Queue Worker
4. WHEN the Scheduler is not running THEN the Dashboard SHALL display setup instructions for starting the Scheduler
5. WHEN both the Queue Worker and Scheduler are running THEN the Dashboard SHALL display a confirmation that the system is properly configured

### Requirement 7

**User Story:** As a developer, I want comprehensive documentation on running the queue and scheduler, so that I can properly configure the development and production environments.

#### Acceptance Criteria

1. WHERE the Application is being set up THEN the documentation SHALL include instructions for starting the Queue Worker
2. WHERE the Application is being set up THEN the documentation SHALL include instructions for starting the Scheduler
3. WHERE a developer is troubleshooting queue issues THEN the documentation SHALL include common problems and their solutions
4. WHERE the Application is running in a development environment THEN the documentation SHALL recommend using `ddev composer dev` command which includes the Queue Worker
5. WHERE the Application is running in a production environment THEN the documentation SHALL recommend using supervisor or systemd for managing Queue Worker and Scheduler processes
