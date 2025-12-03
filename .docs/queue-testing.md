# Queue System Testing Guide

## Overview

The uptime monitor uses Laravel's queue system to run background jobs for checking website availability. This ensures that HTTP requests to monitored sites don't block the web interface.

The application provides built-in diagnostic tools to help you verify that your queue system and scheduler are properly configured and running.

## Queue Configuration

- **Driver**: Database (`QUEUE_CONNECTION=database` in `.env`)
- **Jobs Table**: Created by migration `0001_01_01_000002_create_jobs_table.php`
- **Failed Jobs**: Tracked in `failed_jobs` table

## Quick Start: Testing the Queue System

### 1. Start the Queue Worker

In one terminal, start the queue worker:

```bash
ddev artisan queue:work --tries=1
```

Or use the full development environment (recommended):

```bash
ddev composer dev
```

This starts the server, queue worker, log viewer, and Vite concurrently.

### 2. Start the Scheduler

In another terminal (if not using `ddev composer dev`), start the scheduler:

```bash
ddev artisan schedule:work
```

The scheduler runs every minute and dispatches monitor check jobs automatically.

## Using the "Test Queue" Button

The dashboard includes a "Test Queue" button that allows you to verify your queue system is working correctly.

### How to Use

1. **Navigate to the Dashboard**: Log in and go to the main dashboard page
2. **Locate the Queue Status Widget**: At the top of the page, you'll see a widget displaying queue diagnostics
3. **Click "Test Queue"**: This button dispatches a test job to the queue system
4. **Check the Feedback**: You'll see a success message with instructions to check the logs

### What Happens

When you click "Test Queue":
- A `TestQueueJob` is dispatched to the queue
- The job appears in the `jobs` table with pending status
- If the queue worker is running, it processes the job within seconds
- The job logs a success message: "Test queue job processed successfully"
- The job is removed from the `jobs` table

### Verifying Success

**Option 1: Check the Logs**

Watch logs in real-time:

```bash
ddev artisan pail
```

Look for the message: `Test queue job processed successfully`

**Option 2: Check the Jobs Table**

If the queue worker is NOT running, the job will remain in the database:

```bash
ddev artisan tinker
>>> DB::table('jobs')->count()
```

If the count is greater than 0, the queue worker is not processing jobs.

## Using the "Check Now" Button

Each monitor has a "Check Now" button that allows you to manually trigger an immediate check without waiting for the scheduled interval.

### How to Use

**From the Monitors List:**
1. Navigate to the Monitors page (`/monitors`)
2. Find the monitor you want to check
3. Click the "Check Now" button next to the monitor

**From the Monitor Detail Page:**
1. Navigate to a specific monitor (`/monitors/{id}`)
2. Click the "Check Now" button at the top of the page

### What Happens

When you click "Check Now":
- A `PerformMonitorCheck` job is dispatched to the queue
- You'll see a confirmation message: "Check has been queued"
- The queue worker processes the job (if running)
- The monitor's status and `last_checked_at` timestamp are updated
- If the dashboard auto-refresh is active, you'll see the update within 30 seconds

### Use Cases

- **Immediate Verification**: Test a newly added monitor right away
- **Troubleshooting**: Check if a site is back up after an outage
- **Configuration Testing**: Verify monitor settings are correct
- **Development**: Test changes to check logic without waiting

## Interpreting Queue Diagnostics

The Queue Status Widget on the dashboard provides comprehensive diagnostics about your queue system health.

### Queue Worker Status

**Indicator**: Green checkmark (✓) or Red X (✗)

- **Running**: Queue worker is actively processing jobs
- **Not Running**: No queue worker detected

**How It's Determined**: The system checks for "stuck jobs" - jobs that have been pending for more than 5 minutes. If stuck jobs exist, the worker is likely not running.

### Scheduler Status

**Indicator**: Green checkmark (✓) or Red X (✗)

- **Running**: Scheduler is executing scheduled tasks
- **Not Running**: Scheduler hasn't run in the last 90 seconds

**How It's Determined**: The scheduler updates a heartbeat cache key every minute. If this key is missing or older than 90 seconds, the scheduler is not running.

### Pending Jobs Count

**What It Shows**: Number of jobs waiting to be processed

- **0 jobs**: Queue is clear, all jobs processed
- **1-10 jobs**: Normal operation, jobs being processed
- **10+ jobs**: Possible backlog, consider scaling workers

### Failed Jobs (Last Hour)

**What It Shows**: Number of jobs that failed in the last 60 minutes

- **0 jobs**: All jobs completing successfully
- **1-5 jobs**: Some failures, investigate logs
- **5+ jobs**: Significant issues, immediate attention needed

### Stuck Jobs Count

**What It Shows**: Jobs pending for more than 5 minutes

- **0 jobs**: Queue worker is processing jobs normally
- **1+ jobs**: Queue worker likely not running or severely backed up

**Warning**: If stuck jobs are detected, you'll see a warning message with instructions to start the queue worker.

### System Configuration Status

The widget displays one of three states:

1. **✓ System Properly Configured** (Green)
   - Both queue worker and scheduler are running
   - All systems operational

2. **⚠ Queue Worker Not Running** (Yellow/Orange)
   - Scheduler is running but queue worker is not
   - Manual checks and automatic checks won't be processed
   - Action required: Start the queue worker

3. **⚠ Scheduler Not Running** (Yellow/Orange)
   - Queue worker is running but scheduler is not
   - Manual checks work, but automatic checks won't be scheduled
   - Action required: Start the scheduler

4. **⚠ Both Not Running** (Red)
   - Neither queue worker nor scheduler is running
   - System is not functional
   - Action required: Start both services

## Troubleshooting Common Issues

### Issue: Queue Worker Not Processing Jobs

**Symptoms:**
- Stuck jobs count > 0
- Test jobs remain in the database
- Monitor checks never complete
- Queue status shows "Queue Worker Not Running"

**Solutions:**

1. **Check if the queue worker is running:**
   ```bash
   ddev exec ps aux | grep queue
   ```

2. **Start the queue worker:**
   ```bash
   ddev artisan queue:work --tries=1
   ```
   
   Or use the full dev environment:
   ```bash
   ddev composer dev
   ```

3. **Check for errors in logs:**
   ```bash
   ddev artisan pail
   ```

4. **Verify database connection:**
   ```bash
   ddev artisan tinker
   >>> DB::connection()->getPdo()
   ```

### Issue: Scheduler Not Running

**Symptoms:**
- Scheduler status shows "Not Running"
- Monitors remain in "pending" status
- Automatic checks never execute
- Heartbeat cache key is missing or stale

**Solutions:**

1. **Start the scheduler:**
   ```bash
   ddev artisan schedule:work
   ```

2. **Verify scheduler is running:**
   ```bash
   ddev exec ps aux | grep schedule
   ```

3. **Check scheduled tasks:**
   ```bash
   ddev artisan schedule:list
   ```

4. **Manually run scheduled tasks (for testing):**
   ```bash
   ddev artisan schedule:run
   ```

### Issue: Jobs Failing Repeatedly

**Symptoms:**
- Failed jobs count increasing
- Error messages in logs
- Monitors not updating

**Solutions:**

1. **View failed jobs:**
   ```bash
   ddev artisan queue:failed
   ```

2. **Check the error details:**
   Look at the `exception` column in the `failed_jobs` table or use:
   ```bash
   ddev artisan tinker
   >>> DB::table('failed_jobs')->latest()->first()->exception
   ```

3. **Common causes:**
   - Network timeouts (increase timeout in monitor settings)
   - Invalid URLs (check monitor URL format)
   - Database connection issues (check `.env` configuration)
   - Memory limits (increase PHP memory limit)

4. **Retry failed jobs:**
   ```bash
   ddev artisan queue:retry all
   ```

5. **Clear failed jobs (after fixing the issue):**
   ```bash
   ddev artisan queue:flush
   ```

### Issue: Dashboard Not Auto-Refreshing

**Symptoms:**
- Monitor statuses don't update automatically
- Must manually refresh the page to see changes

**Solutions:**

1. **Check browser console for errors:**
   - Open browser DevTools (F12)
   - Look for JavaScript errors or failed network requests

2. **Verify the API endpoint is accessible:**
   ```bash
   curl -H "Cookie: your-session-cookie" https://uptimer.ddev.site/api/monitors
   ```

3. **Check if JavaScript is loaded:**
   - View page source and verify `dashboard-refresh.js` is included
   - Check that Vite is running: `ddev npm run dev`

4. **Clear browser cache:**
   - Hard refresh: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)

### Issue: "Check Now" Button Not Working

**Symptoms:**
- Clicking "Check Now" shows no feedback
- Monitor status doesn't update
- No job appears in the queue

**Solutions:**

1. **Verify authentication:**
   - Ensure you're logged in
   - Check that you own the monitor

2. **Check authorization:**
   - Only monitor owners can trigger manual checks
   - Verify `MonitorPolicy` is working correctly

3. **Check the queue:**
   ```bash
   ddev artisan tinker
   >>> DB::table('jobs')->where('queue', 'default')->get()
   ```

4. **Check for JavaScript errors:**
   - Open browser console
   - Look for CSRF token issues or network errors

### Issue: Monitors Stuck in "Pending" Status

**Symptoms:**
- New monitors never get checked
- `last_checked_at` remains null
- Status stays "pending"

**Solutions:**

1. **Verify scheduler is running:**
   - Check scheduler status in the queue diagnostics widget
   - Start scheduler: `ddev artisan schedule:work`

2. **Manually trigger a check:**
   - Use the "Check Now" button
   - Or dispatch manually:
     ```bash
     ddev artisan tinker
     >>> $monitor = App\Models\Monitor::first()
     >>> App\Jobs\PerformMonitorCheck::dispatch($monitor)
     ```

3. **Check the schedule:**
   ```bash
   ddev artisan schedule:list
   ```
   
   Verify `schedule:monitor-checks` is listed and runs every minute.

4. **Verify monitor configuration:**
   - Check that `check_interval_minutes` is set (default: 5)
   - Ensure monitor is not paused or disabled

### Issue: High Memory Usage or Slow Performance

**Symptoms:**
- Queue worker consuming excessive memory
- Jobs processing slowly
- System becoming unresponsive

**Solutions:**

1. **Restart the queue worker regularly:**
   ```bash
   ddev artisan queue:restart
   ```

2. **Use `--max-jobs` flag:**
   ```bash
   ddev artisan queue:work --tries=1 --max-jobs=1000
   ```

3. **Use `--max-time` flag:**
   ```bash
   ddev artisan queue:work --tries=1 --max-time=3600
   ```

4. **Monitor memory usage:**
   ```bash
   ddev exec ps aux | grep queue
   ```

5. **Check for memory leaks:**
   - Review job code for unbounded loops
   - Ensure proper cleanup in job `handle()` methods

### Issue: Database Connection Errors

**Symptoms:**
- "SQLSTATE" errors in logs
- Jobs failing with database errors
- Queue worker crashing

**Solutions:**

1. **Verify database is running:**
   ```bash
   ddev describe
   ```

2. **Check database connection settings in `.env`:**
   ```
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=db
   DB_USERNAME=db
   DB_PASSWORD=db
   ```

3. **Test database connection:**
   ```bash
   ddev artisan tinker
   >>> DB::connection()->getPdo()
   ```

4. **Restart DDEV:**
   ```bash
   ddev restart
   ```

5. **Check database logs:**
   ```bash
   ddev logs -s db
   ```

## Advanced Queue Management

### Viewing Queue Statistics

Check queue status programmatically:

```bash
ddev artisan tinker
>>> DB::table('jobs')->count()  # Pending jobs
>>> DB::table('failed_jobs')->count()  # Failed jobs
>>> DB::table('jobs')->where('created_at', '<', now()->subMinutes(5))->count()  # Stuck jobs
```

### Clearing the Queue

Remove all pending jobs:

```bash
ddev artisan tinker
>>> DB::table('jobs')->truncate()
```

**Warning**: This will delete all pending jobs. Use with caution.

### Monitoring Queue in Real-Time

Watch the queue table for changes:

```bash
watch -n 1 'ddev exec mysql -e "SELECT COUNT(*) as pending_jobs FROM db.jobs"'
```

## How Monitor Checks Work

The automatic monitor checking system follows this pattern:

1. **Scheduler** runs every minute (`ScheduleMonitorChecks` command)
2. **Finds monitors** that are due for checking based on `check_interval_minutes`
3. **Dispatches jobs** (`PerformMonitorCheck`) to the queue for each monitor
4. **Queue worker** processes jobs asynchronously
5. **CheckService** performs HTTP request and records result
6. **NotificationService** sends alerts if status changes

### Manual vs Automatic Checks

**Automatic Checks:**
- Triggered by the scheduler every minute
- Only checks monitors that are due (based on `check_interval_minutes`)
- Requires both scheduler and queue worker to be running

**Manual Checks:**
- Triggered by clicking "Check Now" button
- Checks the monitor immediately (queues the job right away)
- Only requires queue worker to be running (scheduler not needed)

## Best Practices

### Development Environment

1. **Use `ddev composer dev`**: This starts all required services in one command
2. **Keep logs visible**: Run `ddev artisan pail` in a separate terminal to monitor activity
3. **Test frequently**: Use the "Test Queue" button to verify queue health
4. **Monitor diagnostics**: Keep an eye on the queue status widget

### Testing Workflow

1. Start the development environment:
   ```bash
   ddev composer dev
   ```

2. Open the dashboard and verify:
   - ✓ Queue Worker Running
   - ✓ Scheduler Running
   - 0 Stuck Jobs

3. Create a test monitor:
   - Add a monitor with a short check interval (1-2 minutes)
   - Click "Check Now" to verify immediate checking works
   - Wait for automatic check to verify scheduler works

4. Monitor the logs:
   ```bash
   ddev artisan pail
   ```

5. Check the results:
   - Verify monitor status updates
   - Check that `last_checked_at` timestamp updates
   - Confirm notifications are sent (if configured)

### Debugging Tips

1. **Enable verbose logging**: Set `LOG_LEVEL=debug` in `.env`
2. **Use Tinker for inspection**: `ddev artisan tinker` is your friend
3. **Check the database directly**: Use `ddev mysql` to query tables
4. **Monitor network requests**: Use browser DevTools to debug AJAX calls
5. **Test in isolation**: Test queue worker and scheduler separately before running together

## Queue Status Widget Reference

The queue status widget provides a comprehensive view of your system health. Here's what each section means:

### Status Indicators

| Indicator | Meaning | Action Required |
|-----------|---------|-----------------|
| ✓ Queue Worker Running | Jobs are being processed | None |
| ✗ Queue Worker Not Running | Jobs are accumulating | Start queue worker |
| ✓ Scheduler Running | Automatic checks are scheduled | None |
| ✗ Scheduler Not Running | No automatic checks | Start scheduler |

### Metrics

| Metric | Description | Healthy Range |
|--------|-------------|---------------|
| Pending Jobs | Jobs waiting to be processed | 0-10 |
| Failed Jobs (Last Hour) | Jobs that failed recently | 0-2 |
| Stuck Jobs | Jobs pending > 5 minutes | 0 |

### Warning Messages

**"Queue Worker is not running"**
- Cause: No queue worker process detected
- Impact: Jobs won't be processed
- Solution: Run `ddev artisan queue:work --tries=1`

**"Scheduler is not running"**
- Cause: Scheduler hasn't updated heartbeat in 90+ seconds
- Impact: Automatic checks won't be scheduled
- Solution: Run `ddev artisan schedule:work`

**"Stuck jobs detected"**
- Cause: Jobs have been pending for more than 5 minutes
- Impact: Queue is backed up or worker is not running
- Solution: Check queue worker status and restart if needed

### Setup Instructions

When components are not running, the widget displays setup instructions:

**For Queue Worker:**
```bash
ddev artisan queue:work --tries=1
```

**For Scheduler:**
```bash
ddev artisan schedule:work
```

**For Full Development Environment:**
```bash
ddev composer dev
```

## Production Considerations

For production deployment, see `.docs/deployment.md` for detailed instructions on:

1. **Using Supervisor** to keep queue worker running
2. **Configuring queue driver** (Redis recommended for production)
3. **Setting up monitoring** for queue worker health
4. **Configuring retry logic** and failed job handling
5. **Setting up Laravel Horizon** (optional) for queue monitoring dashboard
6. **Configuring cron** for the scheduler
7. **Scaling queue workers** for high-traffic applications

## Additional Resources

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Task Scheduling Documentation](https://laravel.com/docs/scheduling)
- [DDEV Documentation](https://ddev.readthedocs.io/)
- Project Setup Guide: `.docs/setup.md`
- Deployment Guide: `.docs/deployment.md`
- Usage Guide: `.docs/usage.md`
