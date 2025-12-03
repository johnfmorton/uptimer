# Setup Guide

This guide will help you install and configure the uptime monitoring application.

## Prerequisites

- PHP 8.2 or higher
- Composer 2
- Node.js and NPM
- MySQL 8.4 or SQLite
- DDEV (for local development)

## Quick Setup Checklist

Use this checklist to ensure you've completed all required setup steps:

- [ ] Clone the repository
- [ ] Start DDEV environment (`ddev start`)
- [ ] Run setup script (`ddev composer run setup`)
- [ ] Configure `.env` file (copy from `.env.example`)
- [ ] Run database migrations (`ddev artisan migrate`)
- [ ] Create admin user account
- [ ] **Start queue worker** (included in `ddev composer dev`)
- [ ] **Start scheduler** (included in `ddev composer dev`)
- [ ] Start development environment (`ddev composer dev`)
- [ ] Verify queue worker is running (check dashboard)
- [ ] Verify scheduler is running (check dashboard)
- [ ] Test queue functionality (click "Test Queue" button)
- [ ] Test monitor checks (create a monitor and click "Check Now")
- [ ] Configure email settings (optional, for notifications)
- [ ] Test email configuration (optional)

**⚠️ Critical:** The queue worker and scheduler are **required** for the application to function properly. Always use `ddev composer dev` to ensure both are running.

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd <project-directory>
```

### 2. Start DDEV Environment

```bash
ddev start
```

### 3. Install Dependencies

Run the setup script to install all dependencies and configure the application:

```bash
ddev composer run setup
```

This command will:
- Install PHP dependencies via Composer
- Install Node.js dependencies via NPM
- Generate application key
- Run database migrations
- Build frontend assets

### 4. Configure Environment Variables

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit `.env` and configure the following settings:

#### Application Settings

```env
APP_NAME="Uptime Monitor"
APP_ENV=local
APP_KEY=base64:... # Generated automatically
APP_DEBUG=true
APP_URL=https://kiro-laravel-ddev-skeleton-template.ddev.site
```

#### Database Configuration

**For SQLite (default):**
```env
DB_CONNECTION=sqlite
# DB_DATABASE will default to database/database.sqlite
```

**For MySQL (via DDEV):**
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db
```

#### Queue Configuration

The application uses database-driven queues by default:

```env
QUEUE_CONNECTION=database
```

#### Mail Configuration

Configure your email settings for notifications:

**Using SMTP:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Using Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Using Mailgun:**
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-mailgun-api-key
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Pushover Configuration (Optional)

To enable Pushover push notifications:

1. Sign up for a Pushover account at <https://pushover.net>
2. Create an application to get your API token
3. Note your user key from your account dashboard

```env
PUSHOVER_USER_KEY=your-user-key-here
PUSHOVER_API_TOKEN=your-api-token-here
```

#### HTTP Check Configuration

Configure the timeout for HTTP checks (in seconds):

```env
CHECK_TIMEOUT=30
```

### 5. Run Database Migrations

If you didn't run the setup script, manually run migrations:

```bash
ddev artisan migrate
```

### 6. Create an Admin User

Create your first user account:

```bash
ddev artisan tinker
```

Then in the Tinker console:

```php
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = bcrypt('password');
$user->save();
```

Type `exit` to leave Tinker.

### 7. Start the Development Environment

**⚠️ IMPORTANT: For development, always use `ddev composer dev`**

Start all development services with a single command:

```bash
ddev composer dev
```

This will start:
- Laravel development server (port 8000)
- Queue worker (for background checks)
- Laravel Scheduler (for automatic monitor checks)
- Laravel Pail (real-time logs)
- Vite dev server (for frontend assets on port 3000)

**This is the recommended way to run the application in development** as it ensures all required services are running, including the queue worker and scheduler that are essential for monitor checks to function properly.

### 8. Access the Application

Open your browser and navigate to:

```
https://kiro-laravel-ddev-skeleton-template.ddev.site
```

Log in with the credentials you created in step 6.

## Queue Worker Setup

**✅ REQUIRED:** The application requires a queue worker to process monitor checks in the background.

### Development

**The `ddev composer dev` command automatically starts a queue worker** - this is the recommended approach.

Alternatively, run it manually in a separate terminal:

```bash
ddev artisan queue:work --tries=1
```

**Why is this required?**
- Monitor checks are dispatched as background jobs
- Manual "Check Now" actions are queued for processing
- Without a queue worker, checks will remain in "pending" status

### Production

For production environments, use a process manager like Supervisor to keep the queue worker running.

**Supervisor Configuration Example:**

Create `/etc/supervisor/conf.d/uptime-monitor-worker.conf`:

```ini
[program:uptime-monitor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/application/artisan queue:work --tries=1 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/application/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start uptime-monitor-worker:*
```

## Scheduler Setup

**✅ REQUIRED:** The application uses Laravel's scheduler to trigger monitor checks automatically at their configured intervals.

### Development

**The `ddev composer dev` command automatically starts the scheduler** - this is the recommended approach.

Alternatively, run it manually in a separate terminal:

```bash
ddev artisan schedule:work
```

**Why is this required?**
- Monitors are checked automatically based on their check interval
- Without the scheduler, monitors will never be checked automatically
- You would need to manually trigger each check using the "Check Now" button

### Production

Add this cron entry to run the scheduler every minute:

```bash
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Edit your crontab:

```bash
crontab -e
```

Add the line above, replacing `/path/to/application` with your actual application path.

## Verification

After completing the setup, verify that all components are working correctly.

### Verify Queue Worker and Scheduler Status

The application includes a built-in queue diagnostics widget on the dashboard that shows:
- Queue worker status (running/not running)
- Scheduler status (running/not running)
- Pending jobs count
- Failed jobs count
- Stuck jobs count

**To verify:**

1. Log in to the application
2. View the dashboard
3. Check the queue status widget at the top of the page
4. Both "Queue Worker" and "Scheduler" should show as "Running" with green indicators

**If either shows as "Not Running":**
- Ensure you started the application with `ddev composer dev`
- Or manually start the missing service (see Queue Worker Setup or Scheduler Setup sections)

### Test Queue Functionality

Use the built-in "Test Queue" button to verify the queue system is working:

1. Log in to the application
2. View the dashboard
3. Click the "Test Queue" button in the queue status widget
4. You should see a success message: "Test job dispatched! Check the logs to verify it was processed."
5. Check the logs to confirm the job was processed:

```bash
ddev artisan pail
```

Look for a log entry indicating successful queue processing.

**Expected behavior:**
- If the queue worker is running: The job will be processed immediately and logged
- If the queue worker is not running: The job will remain in the jobs table with pending status

### Test Monitor Checks

Verify that monitor checks are working:

1. Create a test monitor (e.g., monitoring `https://example.com`)
2. Click the "Check Now" button on the monitor
3. The monitor status should update within a few seconds
4. The "Last Checked" timestamp should update

**Expected behavior:**
- If the queue worker is running: The check will be processed immediately
- If the queue worker is not running: The check will remain in "pending" status

### Test Automatic Scheduling

Verify that the scheduler is triggering automatic checks:

1. Create a monitor with a short check interval (e.g., 1 minute)
2. Wait for the check interval to pass
3. The monitor should be checked automatically
4. The "Last Checked" timestamp should update

**Expected behavior:**
- If the scheduler is running: Monitors will be checked automatically at their configured intervals
- If the scheduler is not running: Monitors will only be checked when you click "Check Now"

### Test Email Configuration

Send a test email to verify your mail configuration:

```bash
ddev artisan tinker
```

```php
Mail::raw('Test email from Uptime Monitor', function($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email');
});
```

### Verify Database Connection

Check that the database is accessible:

```bash
ddev artisan migrate:status
```

You should see a list of migrations with their status.

## Troubleshooting

### Common Setup Issues

#### Queue Worker Not Running

**Problem:** Dashboard shows "Queue Worker: Not Running" or monitors remain in "pending" status.

**Symptoms:**
- Queue status widget shows red indicator for queue worker
- Monitor checks are not being processed
- "Check Now" button doesn't update monitor status
- Test queue jobs remain in the jobs table

**Solution:**

1. **Check if `ddev composer dev` is running:**
   - This command automatically starts the queue worker
   - If not running, start it: `ddev composer dev`

2. **Manually start the queue worker:**
   ```bash
   ddev artisan queue:work --tries=1
   ```

3. **Check for stuck jobs:**
   ```bash
   ddev artisan queue:failed
   ```
   
   If there are failed jobs, retry them:
   ```bash
   ddev artisan queue:retry all
   ```

4. **Clear the jobs table if needed:**
   ```bash
   ddev artisan queue:flush
   ```

5. **Verify the queue connection in `.env`:**
   ```env
   QUEUE_CONNECTION=database
   ```

#### Scheduler Not Running

**Problem:** Dashboard shows "Scheduler: Not Running" or monitors are not checked automatically.

**Symptoms:**
- Queue status widget shows red indicator for scheduler
- Monitors are never checked automatically
- Must manually click "Check Now" for each monitor
- Monitors remain in "pending" status past their check interval

**Solution:**

1. **Check if `ddev composer dev` is running:**
   - This command automatically starts the scheduler
   - If not running, start it: `ddev composer dev`

2. **Manually start the scheduler:**
   ```bash
   ddev artisan schedule:work
   ```

3. **Verify the scheduler heartbeat:**
   - The scheduler updates a cache key every minute
   - If the dashboard shows "Not Running", the heartbeat is stale
   - Restart the scheduler to fix this

4. **Check scheduled commands:**
   ```bash
   ddev artisan schedule:list
   ```
   
   You should see the `schedule:monitor-checks` command listed.

#### Monitors Stuck in "Pending" Status

**Problem:** Monitors never update from "pending" status.

**Symptoms:**
- All monitors show "pending" status
- "Last Checked" shows "Never"
- Dashboard shows warnings about stale monitors

**Solution:**

1. **Verify both queue worker and scheduler are running:**
   - Check the queue status widget on the dashboard
   - Both should show green "Running" indicators

2. **Start the development environment properly:**
   ```bash
   ddev composer dev
   ```

3. **Manually trigger a check to test:**
   - Click "Check Now" on a monitor
   - If it updates, the queue worker is working
   - If it doesn't update, the queue worker is not running

4. **Check for errors in logs:**
   ```bash
   ddev artisan pail
   ```

#### Test Queue Button Not Working

**Problem:** Clicking "Test Queue" button doesn't dispatch a job.

**Symptoms:**
- No success message appears
- No log entry is created
- Jobs table doesn't show the test job

**Solution:**

1. **Check authentication:**
   - Ensure you're logged in
   - The test queue endpoint requires authentication

2. **Check the queue worker:**
   - The job may be dispatched but not processed
   - Verify the queue worker is running

3. **Check application logs:**
   ```bash
   ddev artisan pail
   ```

#### Queue Jobs Not Processing

**Problem:** Jobs are queued but never processed.

**Solution:**
- Ensure the queue worker is running: `ddev artisan queue:work --tries=1`
- Check failed jobs: `ddev artisan queue:failed`
- Retry failed jobs: `ddev artisan queue:retry all`
- Check for errors in logs: `ddev artisan pail`

#### Email Notifications Not Sending

**Problem:** Status change notifications are not being received.

**Solution:**
- Verify mail configuration in `.env`
- Test email sending using Tinker (see Verification section)
- Check application logs: `ddev artisan pail`
- Ensure notification settings are enabled in your user profile
- For Gmail, ensure you're using an app password, not your regular password

#### Database Connection Errors

**Problem:** Cannot connect to database.

**Solution:**
- For SQLite: Ensure `database/database.sqlite` exists
  ```bash
  ddev artisan migrate
  ```
- For MySQL: Verify DDEV is running: `ddev describe`
- Check database credentials in `.env`
- Restart DDEV: `ddev restart`

#### Permission Errors

**Problem:** File permission errors in storage or cache directories.

**Solution:**
```bash
ddev exec chmod -R 775 storage bootstrap/cache
ddev exec chown -R www-data:www-data storage bootstrap/cache
```

#### DDEV Not Starting

**Problem:** DDEV fails to start.

**Solution:**
```bash
ddev poweroff
ddev start
```

If issues persist:
```bash
ddev debug test
```

#### Port Conflicts

**Problem:** DDEV reports port conflicts.

**Solution:**
- Stop other services using the same ports
- Or configure DDEV to use different ports in `.ddev/config.yaml`
- Common conflicts: port 80, 443, 3306

#### Assets Not Loading

**Problem:** CSS/JS assets are not loading or showing 404 errors.

**Solution:**
1. **Ensure Vite dev server is running:**
   - Included in `ddev composer dev`
   - Or run manually: `ddev npm run dev`

2. **Build assets for production:**
   ```bash
   ddev npm run build
   ```

3. **Clear view cache:**
   ```bash
   ddev artisan view:clear
   ```

## Next Steps

Once installation is complete, proceed to the [Usage Guide](usage.md) to learn how to:
- Add and manage monitors
- Configure notification settings
- View uptime statistics
- Interpret check history
