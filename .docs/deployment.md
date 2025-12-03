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

#### Determining Worker Count

The number of workers needed depends on:
- Number of monitors being checked
- Check frequency (check_interval_minutes)
- Average check duration
- Server resources (CPU, memory)

**Example calculation:**
- 100 monitors with 5-minute intervals = 20 checks per minute
- Average check duration: 2 seconds
- Required capacity: 20 checks/min × 2 sec = 40 seconds of work per minute
- Recommended workers: 2-3 (provides headroom for spikes)

#### Adjusting Worker Count

Edit the Supervisor configuration and change `numprocs`:

```ini
numprocs=4  # Increase from 2 to 4 workers
```

Then reload:

```bash
sudo supervisorctl reread
sudo supervisorctl update
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

## Queue Monitoring and Maintenance

### Monitoring Queue Health

#### 1. Queue Size Monitoring

Monitor the number of pending jobs:

```bash
php artisan queue:monitor database --max=100
```

This command will fail if the queue exceeds 100 jobs, which you can integrate with monitoring tools.

#### 2. Failed Jobs

Check for failed jobs regularly:

```bash
# List failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry <job-id>

# Delete failed job
php artisan queue:forget <job-id>

# Flush all failed jobs
php artisan queue:flush
```

#### 3. Worker Process Monitoring

Ensure workers are running:

```bash
# With Supervisor
sudo supervisorctl status uptime-monitor-worker:*

# With systemd
sudo systemctl status uptime-monitor-worker@*

# Check process list
ps aux | grep "queue:work"
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

### Queue Maintenance Tasks

#### Daily Maintenance

```bash
# Prune failed jobs older than 7 days
php artisan queue:prune-failed --hours=168

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
```

#### Monthly Maintenance

```bash
# Archive old check records (optional)
php artisan tinker
>>> Check::where('checked_at', '<', now()->subDays(90))->delete();

# Optimize database
php artisan db:optimize  # If available, or run MySQL OPTIMIZE TABLE
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
