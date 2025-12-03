# Queue System Testing Guide

## Overview

The uptime monitor uses Laravel's queue system to run background jobs for checking website availability. This ensures that HTTP requests to monitored sites don't block the web interface.

## Queue Configuration

- **Driver**: Database (`QUEUE_CONNECTION=database` in `.env`)
- **Jobs Table**: Created by migration `0001_01_01_000002_create_jobs_table.php`
- **Failed Jobs**: Tracked in `failed_jobs` table

## Testing the Queue System

### 1. Start the Queue Worker

In one terminal, start the queue worker:

```bash
ddev artisan queue:work --tries=1
```

Or use the full development environment:

```bash
ddev composer dev
```

This starts the server, queue worker, log viewer, and Vite concurrently.

### 2. Test via Dashboard

1. Log in to the application at `https://uptimer.ddev.site/login`
   - Email: `test@example.com`
   - Password: `password`

2. On the dashboard, click the "Dispatch Test Job" button

3. The job will be queued and processed by the queue worker

### 3. Monitor Job Execution

Watch the logs in real-time:

```bash
ddev artisan pail
```

You should see:
- "Test Queue Job Executed" log entry
- 2-second delay (simulated work)
- "Test Queue Job Completed" log entry

### 4. Check Queue Status

View pending jobs:

```bash
ddev artisan queue:work --once
```

View failed jobs:

```bash
ddev artisan queue:failed
```

Retry failed jobs:

```bash
ddev artisan queue:retry all
```

Clear failed jobs:

```bash
ddev artisan queue:flush
```

## How Monitor Checks Will Work

When implemented, monitor checks will follow this pattern:

1. **Scheduler** runs every minute (`ScheduleMonitorChecks` command)
2. **Finds monitors** that are due for checking based on `check_interval_minutes`
3. **Dispatches jobs** (`PerformMonitorCheck`) to the queue for each monitor
4. **Queue worker** processes jobs asynchronously
5. **CheckService** performs HTTP request and records result
6. **NotificationService** sends alerts if status changes

## Troubleshooting

### Queue Worker Not Processing Jobs

Check if the queue worker is running:

```bash
ddev exec ps aux | grep queue
```

Restart the queue worker:

```bash
# Stop with Ctrl+C, then restart
ddev artisan queue:work --tries=1
```

### Jobs Failing

Check the `failed_jobs` table:

```bash
ddev artisan queue:failed
```

View detailed error information and retry if needed.

### Database Connection Issues

Ensure the database is running:

```bash
ddev describe
```

Check database connection in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
```

## Production Considerations

For production deployment:

1. **Use Supervisor** to keep queue worker running
2. **Configure queue driver** (Redis recommended for production)
3. **Set up monitoring** for queue worker health
4. **Configure retry logic** and failed job handling
5. **Set up Laravel Horizon** (optional) for queue monitoring dashboard

See `.docs/deployment.md` (to be created) for detailed production setup.
