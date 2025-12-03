# Setup Guide

This guide will help you install and configure the uptime monitoring application.

## Prerequisites

- PHP 8.2 or higher
- Composer 2
- Node.js and NPM
- MySQL 8.4 or SQLite
- DDEV (for local development)

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

Start all development services:

```bash
ddev composer run dev
```

This will start:
- Laravel development server
- Queue worker (for background checks)
- Laravel Pail (real-time logs)
- Vite dev server (for frontend assets)

### 8. Access the Application

Open your browser and navigate to:

```
https://kiro-laravel-ddev-skeleton-template.ddev.site
```

Log in with the credentials you created in step 6.

## Queue Worker Setup

The application requires a queue worker to process monitor checks in the background.

### Development

The `ddev composer run dev` command automatically starts a queue worker.

Alternatively, run it manually:

```bash
ddev artisan queue:work --tries=1
```

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

The application uses Laravel's scheduler to trigger monitor checks every minute.

### Development

The scheduler is not required in development as you can manually trigger checks or rely on the queue worker.

### Production

Add this cron entry to run the scheduler:

```bash
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Edit your crontab:

```bash
crontab -e
```

Add the line above, replacing `/path/to/application` with your actual application path.

## Verification

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

### Test Queue Worker

Verify the queue worker is processing jobs:

```bash
ddev artisan queue:work --tries=1
```

You should see output when jobs are processed.

### Test Scheduler

Manually run the scheduler to verify it works:

```bash
ddev artisan schedule:run
```

## Troubleshooting

### Queue Jobs Not Processing

**Problem:** Monitor checks are not being performed.

**Solution:**
- Ensure the queue worker is running: `ddev artisan queue:work`
- Check failed jobs: `ddev artisan queue:failed`
- Retry failed jobs: `ddev artisan queue:retry all`

### Email Notifications Not Sending

**Problem:** Status change notifications are not being received.

**Solution:**
- Verify mail configuration in `.env`
- Test email sending using Tinker (see Verification section)
- Check application logs: `ddev artisan pail`
- Ensure notification settings are enabled in your user profile

### Database Connection Errors

**Problem:** Cannot connect to database.

**Solution:**
- For SQLite: Ensure `database/database.sqlite` exists
- For MySQL: Verify DDEV is running: `ddev describe`
- Check database credentials in `.env`

### Permission Errors

**Problem:** File permission errors in storage or cache directories.

**Solution:**
```bash
ddev exec chmod -R 775 storage bootstrap/cache
ddev exec chown -R www-data:www-data storage bootstrap/cache
```

### DDEV Not Starting

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

## Next Steps

Once installation is complete, proceed to the [Usage Guide](usage.md) to learn how to:
- Add and manage monitors
- Configure notification settings
- View uptime statistics
- Interpret check history
