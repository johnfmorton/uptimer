# Deployment Guide

This guide covers deploying the uptime monitoring application to production environments, with a focus on queue worker configuration, scheduler setup, and monitoring.

## Prerequisites

Before deploying to production, ensure you have:

- PHP 8.2 or higher installed on the server
- Composer 2 for dependency management
- Web server (Nginx or Apache) configured for Laravel
- MySQL 8.4 or PostgreSQL database
- SSL certificate for HTTPS
- Process manager (Supervisor recommended)
- Cron access for Laravel scheduler

## Initial Setup After Deployment

### Create Your First Admin User

After deploying the application and running migrations, you need to create an admin user to access the system. Public registration is disabled by default for security.

#### Method 1: Using the Artisan Command (Recommended)

The easiest way to create an admin user is using the built-in artisan command:

```bash
php artisan user:create-admin
```

The command will prompt you for:
- Name
- Email address
- Password (minimum 8 characters)

**Non-interactive mode** (useful for scripts):

```bash
php artisan user:create-admin \
  --name="Admin User" \
  --email="admin@example.com" \
  --password="your-secure-password"
```

#### Method 2: Using Tinker (Manual)

Alternatively, you can create a user manually using Laravel's Tinker REPL:

```bash
php artisan tinker
```

Then run the following PHP code:

```php
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = bcrypt('your-secure-password');
$user->save();
exit
```

**Important Security Notes:**
- Use a strong, unique password for production
- Store credentials securely (password manager recommended)
- Consider changing the password after first login
- Never commit credentials to version control

## Production Environment Configuration

### Environment Variables

Configure your production `.env` file with appropriate settings:

```env
APP_NAME="Uptime Monitor"
APP_ENV=production
APP_KEY=base64:... # Generate with: php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uptime_monitor
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Queue Configuration
QUEUE_CONNECTION=database
# For better performance, consider Redis:
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Pushover (Optional)
PUSHOVER_USER_KEY=your-pushover-user-key
PUSHOVER_API_TOKEN=your-pushover-api-token

# HTTP Check Configuration
CHECK_TIMEOUT=30
```

### Security Considerations

1. **Never commit `.env` to version control**
2. **Use strong database passwords**
3. **Enable HTTPS only** (set `APP_URL` to https://)
4. **Disable debug mode** (`APP_DEBUG=false`)
5. **Restrict file permissions**:
   ```bash
   chmod -R 755 /path/to/application
   chmod -R 775 /path/to/application/storage
   chmod -R 775 /path/to/application/bootstrap/cache
   ```

## Queue Worker Configuration

The application relies on queue workers to process monitor checks asynchronously. Proper queue worker configuration is critical for production reliability.

### Why Queue Workers Matter

Queue workers:
- Execute HTTP checks in the background without blocking the web interface
- Process multiple checks concurrently for better performance
- Isolate check failures from the main application
- Enable horizontal scaling by adding more workers

### Supervisor Configuration (Recommended)

Supervisor is a process control system that keeps your queue workers running continuously, automatically restarting them if they crash.

#### Installation

**Ubuntu/Debian:**
```bash
sudo apt-get install supervisor
```

**CentOS/RHEL:**
```bash
sudo yum install supervisor
sudo systemctl enable supervisord
sudo systemctl start supervisord
```

#### Configuration File

Create a Supervisor configuration file at `/etc/supervisor/conf.d/uptime-monitor-worker.conf`:

```ini
[program:uptime-monitor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/uptime-monitor/artisan queue:work database --tries=1 --timeout=90 --sleep=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/uptime-monitor/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
startsecs=0
```

#### Configuration Explanation

- **process_name**: Unique name for each worker process
- **command**: The queue worker command with options:
  - `database`: Queue connection to use (matches `QUEUE_CONNECTION` in `.env`)
  - `--tries=1`: Number of times to attempt a job before marking it as failed
  - `--timeout=90`: Maximum seconds a job can run (should exceed `CHECK_TIMEOUT`)
  - `--sleep=3`: Seconds to sleep when no jobs are available
  - `--max-time=3600`: Restart worker after 1 hour to prevent memory leaks
- **autostart**: Start workers when Supervisor starts
- **autorestart**: Restart workers if they exit unexpectedly
- **stopasgroup**: Send stop signal to entire process group
- **killasgroup**: Send kill signal to entire process group
- **user**: System user to run the worker (typically `www-data` or `nginx`)
- **numprocs**: Number of worker processes (adjust based on load)
- **redirect_stderr**: Redirect error output to stdout log
- **stdout_logfile**: Log file location
- **stdout_logfile_maxbytes**: Maximum log file size before rotation
- **stdout_logfile_backups**: Number of rotated log files to keep
- **stopwaitsecs**: Seconds to wait for graceful shutdown before killing
- **startsecs**: Seconds process must stay running to be considered started

#### Applying Configuration

After creating the configuration file:

```bash
# Reload Supervisor configuration
sudo supervisorctl reread

# Update Supervisor with new configuration
sudo supervisorctl update

# Start the workers
sudo supervisorctl start uptime-monitor-worker:*

# Check worker status
sudo supervisorctl status uptime-monitor-worker:*
```

#### Managing Workers

```bash
# Start all workers
sudo supervisorctl start uptime-monitor-worker:*

# Stop all workers
sudo supervisorctl stop uptime-monitor-worker:*

# Restart all workers (do this after deploying code changes)
sudo supervisorctl restart uptime-monitor-worker:*

# View worker logs
sudo supervisorctl tail -f uptime-monitor-worker:uptime-monitor-worker_00 stdout

# Check status
sudo supervisorctl status
```

### Scaling Queue Workers

As your monitoring needs grow, you'll need to scale your queue workers to handle increased load. This section provides guidance on determining the right number of workers and scaling strategies.

#### Determining Worker Count

The number of workers needed depends on several factors:

**Key Factors:**
- **Number of monitors**: More monitors = more checks to process
- **Check frequency**: Lower `check_interval_minutes` = more frequent checks
- **Average check duration**: Slower sites require more worker capacity
- **Server resources**: CPU cores, available memory
- **Peak load patterns**: Traffic spikes, time-of-day variations

**Calculation Formula:**

```
Checks per minute = Total monitors / Average check interval (minutes)
Worker capacity = 60 seconds / Average check duration (seconds)
Minimum workers = Checks per minute / Worker capacity
Recommended workers = Minimum workers × 1.5 (for headroom)
```

**Example Calculation:**

Scenario: 100 monitors with 5-minute intervals, 2-second average check duration

```
Checks per minute = 100 monitors / 5 minutes = 20 checks/min
Worker capacity = 60 seconds / 2 seconds = 30 checks/min per worker
Minimum workers = 20 / 30 = 0.67 workers
Recommended workers = 0.67 × 1.5 = 1 worker (round up to 2 for redundancy)
```

**Scaling Guidelines by Monitor Count:**

| Monitors | Check Interval | Recommended Workers | Notes |
|----------|----------------|---------------------|-------|
| 1-50     | 5 min          | 1-2                 | Single worker sufficient |
| 51-200   | 5 min          | 2-3                 | Add redundancy |
| 201-500  | 5 min          | 3-5                 | Consider Redis queue |
| 501-1000 | 5 min          | 5-8                 | Redis queue recommended |
| 1000+    | 5 min          | 8+                  | Dedicated queue server |

#### Adjusting Worker Count

**Method 1: Supervisor (Recommended)**

Edit the Supervisor configuration file `/etc/supervisor/conf.d/uptime-monitor-worker.conf`:

```ini
numprocs=4  # Increase from 2 to 4 workers
```

Apply changes:

```bash
# Reload configuration
sudo supervisorctl reread

# Update with new configuration
sudo supervisorctl update

# Verify new workers are running
sudo supervisorctl status uptime-monitor-worker:*
```

**Method 2: Systemd**

Enable and start additional worker instances:

```bash
# Enable new workers
sudo systemctl enable uptime-monitor-worker@3
sudo systemctl enable uptime-monitor-worker@4

# Start new workers
sudo systemctl start uptime-monitor-worker@3
sudo systemctl start uptime-monitor-worker@4

# Verify all workers are running
sudo systemctl status uptime-monitor-worker@*
```

#### Horizontal Scaling Strategies

**Single Server Scaling:**

1. **Increase worker count** (up to number of CPU cores)
2. **Optimize worker settings** (`--max-time`, `--max-jobs`)
3. **Switch to Redis queue** for better performance
4. **Add more CPU cores** to the server

**Multi-Server Scaling:**

For very large deployments (1000+ monitors):

1. **Dedicated Queue Server:**
   - Separate server for queue workers only
   - Web servers dispatch jobs to shared queue
   - Queue server processes jobs exclusively

2. **Load Balancing:**
   - Multiple web servers behind load balancer
   - Shared database and queue backend (Redis)
   - Workers distributed across multiple servers

3. **Architecture Example:**

   ```
   ┌─────────────┐     ┌─────────────┐
   │  Web Server │────▶│   Redis     │
   │   (Laravel) │     │   (Queue)   │
   └─────────────┘     └─────────────┘
                              │
                              ▼
                       ┌─────────────┐
                       │   Queue     │
                       │   Workers   │
                       │  (2-4 per   │
                       │   server)   │
                       └─────────────┘
   ```

#### Performance Monitoring for Scaling Decisions

Monitor these metrics to determine when to scale:

**1. Queue Depth Over Time:**
```bash
# Log queue size every minute
watch -n 60 'php artisan tinker --execute="echo DB::table(\"jobs\")->count();" >> /var/log/queue-depth.log'
```

**2. Worker CPU Usage:**
```bash
# Monitor worker CPU usage
ps aux | grep "queue:work" | awk '{print $3}' | awk '{s+=$1} END {print s}'
```

**3. Job Processing Rate:**
```bash
# Count jobs processed in last hour
php artisan tinker
>>> $processed = DB::table('checks')->where('checked_at', '>', now()->subHour())->count();
>>> echo "Jobs processed last hour: $processed";
```

**Scaling Indicators:**

- **Scale Up** if:
  - Queue depth consistently > 100 jobs
  - Worker CPU usage > 80%
  - Checks delayed beyond their interval
  - Failed jobs increasing due to timeouts

- **Scale Down** if:
  - Queue depth consistently < 10 jobs
  - Worker CPU usage < 20%
  - Workers idle most of the time

#### Auto-Scaling Considerations

For cloud deployments, consider auto-scaling based on queue depth:

**AWS Auto Scaling Example:**

```bash
# CloudWatch alarm for high queue depth
aws cloudwatch put-metric-alarm \
  --alarm-name high-queue-depth \
  --alarm-description "Scale up when queue depth > 200" \
  --metric-name QueueDepth \
  --namespace Custom/UptimeMonitor \
  --statistic Average \
  --period 300 \
  --threshold 200 \
  --comparison-operator GreaterThanThreshold \
  --evaluation-periods 2
```

**Custom Metric Publishing:**

```php
// Publish queue depth to CloudWatch
$queue_depth = DB::table('jobs')->count();
CloudWatch::putMetric('QueueDepth', $queue_depth, 'Custom/UptimeMonitor');
```

### Alternative: Systemd Service

If you prefer systemd over Supervisor:

Create `/etc/systemd/system/uptime-monitor-worker@.service`:

```ini
[Unit]
Description=Uptime Monitor Queue Worker %i
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/uptime-monitor
ExecStart=/usr/bin/php /var/www/uptime-monitor/artisan queue:work database --tries=1 --timeout=90 --sleep=3 --max-time=3600
Restart=always
RestartSec=3
StandardOutput=append:/var/www/uptime-monitor/storage/logs/worker-%i.log
StandardError=append:/var/www/uptime-monitor/storage/logs/worker-%i.log

[Install]
WantedBy=multi-user.target
```

Enable and start multiple workers:

```bash
# Enable and start 2 workers
sudo systemctl enable uptime-monitor-worker@1
sudo systemctl enable uptime-monitor-worker@2
sudo systemctl start uptime-monitor-worker@1
sudo systemctl start uptime-monitor-worker@2

# Check status
sudo systemctl status uptime-monitor-worker@*
```

## Laravel Scheduler Configuration

The Laravel scheduler triggers the `ScheduleMonitorChecks` command every minute, which dispatches check jobs for monitors that are due for checking.

### Cron Configuration

Add the following cron entry to run the Laravel scheduler:

```bash
* * * * * cd /var/www/uptime-monitor && php artisan schedule:run >> /dev/null 2>&1
```

#### Setting Up Cron

1. **Edit the crontab for the web server user:**

   ```bash
   sudo crontab -e -u www-data
   ```

2. **Add the scheduler entry:**

   ```cron
   * * * * * cd /var/www/uptime-monitor && php artisan schedule:run >> /dev/null 2>&1
   ```

3. **Save and exit**

4. **Verify the cron entry:**

   ```bash
   sudo crontab -l -u www-data
   ```

#### Scheduler Logging (Optional)

To log scheduler output for debugging:

```cron
* * * * * cd /var/www/uptime-monitor && php artisan schedule:run >> /var/www/uptime-monitor/storage/logs/scheduler.log 2>&1
```

### Verifying Scheduler

Test the scheduler manually:

```bash
cd /var/www/uptime-monitor
php artisan schedule:run
```

You should see output indicating which commands were executed.

### Scheduler Monitoring

Monitor scheduler execution by checking:

1. **Application logs:**
   ```bash
   tail -f /var/www/uptime-monitor/storage/logs/laravel.log
   ```

2. **Scheduler log (if configured):**
   ```bash
   tail -f /var/www/uptime-monitor/storage/logs/scheduler.log
   ```

3. **Queue jobs table:**
   ```bash
   php artisan tinker
   >>> DB::table('jobs')->count();
   ```

## Monitoring Queue Health in Production

Proper queue monitoring is essential for maintaining a reliable uptime monitoring system. This section covers comprehensive strategies for monitoring queue health in production environments.

### Real-Time Queue Monitoring

#### 1. Queue Size Monitoring

Monitor the number of pending jobs to detect processing bottlenecks:

```bash
# Monitor queue and fail if it exceeds threshold
php artisan queue:monitor database --max=100
```

This command will fail if the queue exceeds 100 jobs, which you can integrate with monitoring tools.

**Automated Monitoring Script:**

Create `/usr/local/bin/monitor-queue-health.sh`:

```bash
#!/bin/bash
QUEUE_SIZE=$(php /var/www/uptime-monitor/artisan tinker --execute="echo DB::table('jobs')->count();")
FAILED_JOBS=$(php /var/www/uptime-monitor/artisan tinker --execute="echo DB::table('failed_jobs')->where('failed_at', '>', now()->subHour())->count();")

# Alert if queue size exceeds threshold
if [ "$QUEUE_SIZE" -gt 500 ]; then
    echo "WARNING: Queue size is $QUEUE_SIZE (threshold: 500)"
    # Send alert (email, Slack, PagerDuty, etc.)
fi

# Alert if too many recent failures
if [ "$FAILED_JOBS" -gt 50 ]; then
    echo "WARNING: $FAILED_JOBS jobs failed in the last hour"
    # Send alert
fi
```

Add to cron for regular monitoring:

```bash
*/5 * * * * /usr/local/bin/monitor-queue-health.sh
```

#### 2. Failed Jobs Monitoring and Management

Check for failed jobs regularly and establish procedures for handling them:

```bash
# List all failed jobs
php artisan queue:failed

# List failed jobs with details
php artisan queue:failed --verbose

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job by ID
php artisan queue:retry <job-id>

# Retry jobs that failed in the last hour
php artisan queue:retry --range=1-100

# Delete specific failed job
php artisan queue:forget <job-id>

# Flush all failed jobs (use with caution)
php artisan queue:flush
```

**Failed Job Analysis:**

Regularly analyze failed jobs to identify patterns:

```bash
# View failed job details
php artisan tinker
>>> $failed = DB::table('failed_jobs')->latest()->first();
>>> echo $failed->exception;
```

**Common Failure Patterns:**

1. **Network Timeouts**: Increase `CHECK_TIMEOUT` or `--timeout` for workers
2. **Memory Exhaustion**: Reduce `--max-time` or `--max-jobs` for workers
3. **Database Connection Issues**: Check database connection pool settings
4. **External API Failures**: Implement retry logic with exponential backoff

#### 3. Worker Process Monitoring

Ensure workers are running continuously:

```bash
# With Supervisor
sudo supervisorctl status uptime-monitor-worker:*

# With systemd
sudo systemctl status uptime-monitor-worker@*

# Check process list
ps aux | grep "queue:work"

# Count active workers
ps aux | grep "queue:work" | grep -v grep | wc -l
```

**Worker Health Check Script:**

Create `/usr/local/bin/check-workers.sh`:

```bash
#!/bin/bash
EXPECTED_WORKERS=2
ACTUAL_WORKERS=$(ps aux | grep "queue:work" | grep -v grep | wc -l)

if [ "$ACTUAL_WORKERS" -lt "$EXPECTED_WORKERS" ]; then
    echo "ERROR: Only $ACTUAL_WORKERS workers running (expected: $EXPECTED_WORKERS)"
    # Restart workers
    sudo supervisorctl restart uptime-monitor-worker:*
    # Send alert
fi
```

#### 4. Scheduler Health Monitoring

Verify the Laravel scheduler is running correctly:

```bash
# Check if scheduler heartbeat is recent
php artisan tinker
>>> $heartbeat = Cache::get('scheduler:heartbeat');
>>> $age = now()->diffInSeconds($heartbeat);
>>> echo "Scheduler last ran $age seconds ago";
```

**Scheduler Monitoring Script:**

Create `/usr/local/bin/check-scheduler.sh`:

```bash
#!/bin/bash
HEARTBEAT_AGE=$(php /var/www/uptime-monitor/artisan tinker --execute="
    \$heartbeat = Cache::get('scheduler:heartbeat');
    echo \$heartbeat ? now()->diffInSeconds(\$heartbeat) : 999;
")

if [ "$HEARTBEAT_AGE" -gt 120 ]; then
    echo "ERROR: Scheduler hasn't run in $HEARTBEAT_AGE seconds"
    # Send alert
fi
```

Add to cron:

```bash
*/5 * * * * /usr/local/bin/check-scheduler.sh
```

### Monitoring Tools Integration

#### Laravel Horizon (Redis Only)

If using Redis for queues, consider Laravel Horizon for advanced monitoring:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Horizon provides:
- Real-time queue monitoring dashboard
- Job metrics and throughput graphs
- Failed job management
- Worker load balancing

#### External Monitoring Services

Integrate with monitoring services to track queue health:

**1. New Relic:**
- Monitor queue job execution time
- Track failed job rates
- Alert on queue depth thresholds

**2. Datadog:**
- Custom metrics for queue size
- Worker process monitoring
- Alert on worker failures

**3. Prometheus + Grafana:**
- Export queue metrics
- Visualize queue depth over time
- Alert on anomalies

#### Custom Health Check Endpoint

Create a health check endpoint to monitor queue status:

```php
// routes/web.php
Route::get('/health/queue', function () {
    $pending_jobs = DB::table('jobs')->count();
    $failed_jobs = DB::table('failed_jobs')->count();
    
    $healthy = $pending_jobs < 1000 && $failed_jobs < 100;
    
    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'pending_jobs' => $pending_jobs,
        'failed_jobs' => $failed_jobs,
    ], $healthy ? 200 : 503);
});
```

Monitor this endpoint with tools like:
- Pingdom
- UptimeRobot
- StatusCake
- Nagios

## Handling Failed Jobs Procedures

Failed jobs are inevitable in any queue system. This section provides comprehensive procedures for handling, analyzing, and preventing failed jobs.

### Understanding Failed Jobs

Jobs can fail for various reasons:

1. **Network Issues**: Target site unreachable, DNS failures, timeouts
2. **Application Errors**: Bugs in job code, uncaught exceptions
3. **Resource Constraints**: Memory exhaustion, database connection limits
4. **External Service Failures**: Email service down, notification API unavailable
5. **Data Issues**: Invalid monitor configuration, malformed URLs

### Failed Job Workflow

#### 1. Detection and Alerting

Set up automated alerts for failed jobs:

**Alert Script** (`/usr/local/bin/alert-failed-jobs.sh`):

```bash
#!/bin/bash
FAILED_COUNT=$(php /var/www/uptime-monitor/artisan tinker --execute="echo DB::table('failed_jobs')->where('failed_at', '>', now()->subHour())->count();")
THRESHOLD=10

if [ "$FAILED_COUNT" -gt "$THRESHOLD" ]; then
    echo "ALERT: $FAILED_COUNT jobs failed in the last hour (threshold: $THRESHOLD)"
    
    # Send email alert
    echo "Failed jobs alert: $FAILED_COUNT failures" | mail -s "Queue Alert" admin@example.com
    
    # Or send to Slack
    curl -X POST -H 'Content-type: application/json' \
      --data "{\"text\":\"⚠️ Queue Alert: $FAILED_COUNT jobs failed in the last hour\"}" \
      YOUR_SLACK_WEBHOOK_URL
fi
```

Add to cron:

```bash
*/15 * * * * /usr/local/bin/alert-failed-jobs.sh
```

#### 2. Investigation

When failed jobs are detected, investigate the root cause:

**View Failed Job Details:**

```bash
# List recent failed jobs
php artisan queue:failed

# View specific failed job
php artisan tinker
>>> $job = DB::table('failed_jobs')->latest()->first();
>>> echo $job->exception;  // View full exception
>>> echo $job->payload;    // View job data
```

**Analyze Failure Patterns:**

```bash
# Count failures by exception type
php artisan tinker
>>> $failures = DB::table('failed_jobs')
>>>     ->where('failed_at', '>', now()->subDay())
>>>     ->get()
>>>     ->groupBy(function($job) {
>>>         preg_match('/([A-Za-z\\\\]+Exception)/', $job->exception, $matches);
>>>         return $matches[1] ?? 'Unknown';
>>>     })
>>>     ->map->count();
>>> print_r($failures->toArray());
```

**Common Failure Patterns and Solutions:**

| Exception Type | Likely Cause | Solution |
|----------------|--------------|----------|
| `ConnectionException` | Network timeout | Increase `CHECK_TIMEOUT` |
| `QueryException` | Database issue | Check DB connections |
| `MemoryExhaustedException` | Memory limit | Reduce `--max-time` |
| `GuzzleException` | HTTP client error | Check target site |
| `MailException` | Email service down | Verify SMTP settings |

#### 3. Resolution Strategies

**Strategy 1: Automatic Retry**

For transient failures (network issues, temporary outages):

```bash
# Retry all failed jobs
php artisan queue:retry all

# Retry jobs from last hour
php artisan queue:retry --range=1-100

# Retry specific job
php artisan queue:retry <job-id>
```

**Strategy 2: Fix and Retry**

For application bugs:

1. Identify the bug from exception trace
2. Deploy fix to production
3. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```

**Strategy 3: Manual Intervention**

For data issues (invalid monitor configuration):

1. Fix the underlying data:
   ```bash
   php artisan tinker
   >>> $monitor = Monitor::find(123);
   >>> $monitor->url = 'https://corrected-url.com';
   >>> $monitor->save();
   ```

2. Retry the failed job:
   ```bash
   php artisan queue:retry <job-id>
   ```

**Strategy 4: Discard**

For jobs that cannot be recovered:

```bash
# Delete specific failed job
php artisan queue:forget <job-id>

# Flush all failed jobs (use with caution)
php artisan queue:flush
```

#### 4. Prevention

Implement strategies to prevent future failures:

**A. Implement Retry Logic in Jobs:**

```php
// app/Jobs/PerformMonitorCheck.php
class PerformMonitorCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;  // Retry up to 3 times
    public $backoff = [60, 300, 900];  // Exponential backoff: 1min, 5min, 15min
    public $timeout = 90;  // Job timeout
    
    public function retryUntil()
    {
        return now()->addMinutes(30);  // Stop retrying after 30 minutes
    }
}
```

**B. Add Error Handling:**

```php
public function handle()
{
    try {
        // Perform check
        $this->checkService->performCheck($this->monitor);
    } catch (ConnectionException $e) {
        // Log and retry for network issues
        Log::warning("Network error checking {$this->monitor->url}: {$e->getMessage()}");
        $this->release(60);  // Retry in 60 seconds
    } catch (Exception $e) {
        // Log and fail for other errors
        Log::error("Error checking {$this->monitor->url}: {$e->getMessage()}");
        $this->fail($e);
    }
}
```

**C. Implement Circuit Breaker:**

For monitors that consistently fail:

```php
// Disable monitor after 10 consecutive failures
if ($monitor->consecutive_failures >= 10) {
    $monitor->update(['status' => 'disabled']);
    Log::warning("Monitor {$monitor->id} disabled after 10 consecutive failures");
}
```

**D. Add Job Middleware:**

```php
// app/Jobs/Middleware/RateLimitMonitorChecks.php
class RateLimitMonitorChecks
{
    public function handle($job, $next)
    {
        Redis::throttle('monitor-checks')
            ->allow(100)  // 100 checks
            ->every(60)   // per minute
            ->then(function () use ($job, $next) {
                $next($job);
            }, function () use ($job) {
                $job->release(10);  // Release back to queue
            });
    }
}
```

### Failed Job Reporting

Generate regular reports on failed jobs:

**Daily Failed Job Report Script:**

```bash
#!/bin/bash
# /usr/local/bin/failed-jobs-report.sh

REPORT_FILE="/var/log/uptime-monitor/failed-jobs-$(date +%Y%m%d).txt"

echo "Failed Jobs Report - $(date)" > $REPORT_FILE
echo "================================" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Total failed jobs
TOTAL=$(php /var/www/uptime-monitor/artisan tinker --execute="echo DB::table('failed_jobs')->count();")
echo "Total Failed Jobs: $TOTAL" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Failed jobs in last 24 hours
LAST_24H=$(php /var/www/uptime-monitor/artisan tinker --execute="echo DB::table('failed_jobs')->where('failed_at', '>', now()->subDay())->count();")
echo "Failed in Last 24 Hours: $LAST_24H" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Top failure reasons
echo "Top Failure Reasons:" >> $REPORT_FILE
php /var/www/uptime-monitor/artisan tinker --execute="
    DB::table('failed_jobs')
        ->where('failed_at', '>', now()->subDay())
        ->get()
        ->groupBy(function(\$job) {
            preg_match('/([A-Za-z\\\\]+Exception)/', \$job->exception, \$matches);
            return \$matches[1] ?? 'Unknown';
        })
        ->map->count()
        ->sortDesc()
        ->take(5)
        ->each(function(\$count, \$exception) {
            echo \"\$exception: \$count\n\";
        });
" >> $REPORT_FILE

# Email report
mail -s "Failed Jobs Report - $(date +%Y-%m-%d)" admin@example.com < $REPORT_FILE
```

Add to cron:

```bash
0 8 * * * /usr/local/bin/failed-jobs-report.sh
```

### Queue Maintenance Tasks

#### Daily Maintenance

```bash
# Prune failed jobs older than 7 days
php artisan queue:prune-failed --hours=168

# Review failed jobs from last 24 hours
php artisan queue:failed | head -20

# Clear old job records (if using database queue)
# Add to scheduler in app/Console/Kernel.php:
$schedule->command('queue:prune-batches')->daily();
```

#### Weekly Maintenance

```bash
# Review failed jobs
php artisan queue:failed

# Analyze failure patterns
# Check storage/logs/laravel.log for recurring errors

# Generate weekly report
php artisan tinker
>>> $weekly_failures = DB::table('failed_jobs')
>>>     ->where('failed_at', '>', now()->subWeek())
>>>     ->count();
>>> echo "Failed jobs this week: $weekly_failures";
```

#### Monthly Maintenance

```bash
# Archive old check records (optional)
php artisan tinker
>>> Check::where('checked_at', '<', now()->subDays(90))->delete();

# Optimize database tables
mysql -u user -p uptime_monitor -e "OPTIMIZE TABLE jobs, failed_jobs, checks;"

# Review and clean up old failed jobs
php artisan queue:flush  # After reviewing and resolving
```

### Performance Optimization

#### Database Queue Optimization

If using database queues, add indexes for better performance:

```sql
-- Already included in migrations, but verify:
CREATE INDEX jobs_queue_index ON jobs(queue);
CREATE INDEX jobs_reserved_at_index ON jobs(reserved_at);
```

#### Redis Queue (Recommended for High Load)

For better performance with many monitors, switch to Redis:

1. **Install Redis:**
   ```bash
   sudo apt-get install redis-server
   ```

2. **Update `.env`:**
   ```env
   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

3. **Update Supervisor command:**
   ```ini
   command=php /var/www/uptime-monitor/artisan queue:work redis --tries=1 --timeout=90
   ```

4. **Restart workers:**
   ```bash
   sudo supervisorctl restart uptime-monitor-worker:*
   ```

### Troubleshooting

#### Workers Not Processing Jobs

**Symptoms:**
- Monitors not being checked
- Jobs accumulating in queue

**Solutions:**
1. Check worker status:
   ```bash
   sudo supervisorctl status uptime-monitor-worker:*
   ```

2. Check worker logs:
   ```bash
   tail -f /var/www/uptime-monitor/storage/logs/worker.log
   ```

3. Restart workers:
   ```bash
   sudo supervisorctl restart uptime-monitor-worker:*
   ```

4. Check for errors in application log:
   ```bash
   tail -f /var/www/uptime-monitor/storage/logs/laravel.log
   ```

#### High Memory Usage

**Symptoms:**
- Workers consuming excessive memory
- Server running out of memory

**Solutions:**
1. Reduce `--max-time` to restart workers more frequently:
   ```ini
   command=php artisan queue:work --max-time=1800  # 30 minutes
   ```

2. Add `--max-jobs` to restart after processing N jobs:
   ```ini
   command=php artisan queue:work --max-jobs=1000
   ```

3. Monitor memory usage:
   ```bash
   watch -n 5 'ps aux | grep queue:work'
   ```

#### Scheduler Not Running

**Symptoms:**
- No new checks being scheduled
- Monitors not being checked at intervals

**Solutions:**
1. Verify cron entry exists:
   ```bash
   sudo crontab -l -u www-data
   ```

2. Test scheduler manually:
   ```bash
   php artisan schedule:run
   ```

3. Check cron logs:
   ```bash
   sudo grep CRON /var/log/syslog
   ```

4. Ensure correct permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/uptime-monitor
   ```

## Deployment Checklist

Before deploying to production:

- [ ] Configure production `.env` with secure credentials
- [ ] Set `APP_DEBUG=false` and `APP_ENV=production`
- [ ] Run database migrations: `php artisan migrate --force`
- [ ] **Create admin user: `php artisan user:create-admin`**
- [ ] Optimize application: `php artisan optimize`
- [ ] Configure Supervisor for queue workers
- [ ] Set up cron for Laravel scheduler
- [ ] Configure SSL certificate for HTTPS
- [ ] Set up monitoring for queue health
- [ ] Configure email notifications (SMTP)
- [ ] Test Pushover integration (if using)
- [ ] Set up log rotation for application logs
- [ ] Configure firewall rules
- [ ] Set up automated backups for database
- [ ] Document server access credentials securely
- [ ] Test monitor creation and checking
- [ ] Verify notifications are sent correctly

## Post-Deployment Monitoring

After deployment, monitor these metrics:

1. **Queue Depth**: Should remain low (< 100 jobs)
2. **Failed Jobs**: Should be minimal (< 1% of total)
3. **Worker Uptime**: Workers should stay running continuously
4. **Check Success Rate**: Most checks should succeed
5. **Response Times**: Monitor average check response times
6. **Notification Delivery**: Verify emails and Pushover notifications arrive
7. **Server Resources**: Monitor CPU, memory, and disk usage

## Scaling Considerations

As your monitoring needs grow:

1. **Horizontal Scaling**: Add more queue workers
2. **Database Optimization**: Add indexes, consider read replicas
3. **Queue Backend**: Migrate from database to Redis for better performance
4. **Caching**: Implement caching for uptime statistics
5. **Load Balancing**: Distribute web traffic across multiple servers
6. **Separate Queue Server**: Dedicate a server for queue processing

## Security Best Practices

1. **Keep Laravel and dependencies updated**
2. **Use strong passwords for database and admin accounts**
3. **Enable HTTPS only** (redirect HTTP to HTTPS)
4. **Restrict database access** to localhost or specific IPs
5. **Use environment variables** for sensitive configuration
6. **Enable Laravel's security features** (CSRF, XSS protection)
7. **Regular security audits** with `composer audit`
8. **Monitor failed login attempts**
9. **Implement rate limiting** on authentication routes
10. **Regular backups** of database and configuration

## Backup Strategy

### Database Backups

Set up automated daily backups:

```bash
# Create backup script: /usr/local/bin/backup-uptime-monitor.sh
#!/bin/bash
BACKUP_DIR="/var/backups/uptime-monitor"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# MySQL backup
mysqldump -u db_user -p'db_password' uptime_monitor > $BACKUP_DIR/db_$DATE.sql
gzip $BACKUP_DIR/db_$DATE.sql

# Keep only last 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
```

Add to cron:

```bash
0 2 * * * /usr/local/bin/backup-uptime-monitor.sh
```

### Configuration Backups

Backup `.env` and other configuration files regularly:

```bash
tar -czf /var/backups/uptime-monitor/config_$(date +%Y%m%d).tar.gz \
  /var/www/uptime-monitor/.env \
  /etc/supervisor/conf.d/uptime-monitor-worker.conf
```

## Support and Maintenance

For ongoing support:

1. **Monitor application logs** regularly
2. **Review failed jobs** weekly
3. **Update dependencies** monthly
4. **Test disaster recovery** procedures quarterly
5. **Review and optimize** database performance quarterly
6. **Security updates** as soon as available

---

**Next Steps:**
- Review the [Setup Guide](setup.md) for development environment
- See the [Usage Guide](usage.md) for application features
- Check [Environment Variables](environment-variables.md) for configuration reference
