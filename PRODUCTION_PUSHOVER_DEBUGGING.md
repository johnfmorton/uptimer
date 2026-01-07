# Production Pushover Debugging Guide

This guide helps you debug Pushover notification issues specifically in production environments where notifications work locally but fail in production.

## Quick Diagnosis

Your Pushover notifications work in DDEV locally but fail in production. This is typically caused by:

1. **Missing environment variables** in production `.env`
2. **Network connectivity issues** (firewall, DNS, SSL)
3. **Different database/user configuration** in production
4. **Server configuration differences**

## Step-by-Step Debugging Process

### Step 1: Upload and Run the Debug Script

1. Upload the `production-pushover-debug.php` file to your production server
2. Run it via command line:
   ```bash
   php production-pushover-debug.php
   ```

This script will test:
- Environment variables
- Database configuration
- Network connectivity
- Laravel HTTP client
- Notification service
- Log file status

### Step 2: Check Environment Variables

Ensure your production `.env` file contains:

```env
PUSHOVER_USER_KEY=your_30_character_user_key
PUSHOVER_API_TOKEN=your_30_character_api_token
```

**Common Issues:**
- Variables not set in production `.env`
- Typos in variable names
- Extra spaces or quotes around values
- Different `.env` file location in production

**Verification:**
```bash
# On production server
grep PUSHOVER /path/to/your/.env
```

### Step 3: Test Network Connectivity

Run these commands on your production server:

```bash
# Test DNS resolution
nslookup api.pushover.net

# Test HTTPS connectivity
curl -I https://api.pushover.net

# Test with actual credentials
curl -X POST https://api.pushover.net/1/messages.json \
  -d "token=YOUR_API_TOKEN" \
  -d "user=YOUR_USER_KEY" \
  -d "message=Production test"
```

**Common Issues:**
- Firewall blocking outbound HTTPS (port 443)
- DNS resolution failures
- SSL certificate validation issues
- Proxy server interference

### Step 4: Check Database Configuration

Verify users have Pushover properly configured:

```bash
# On production server
php artisan tinker --execute="
echo 'Users with Pushover enabled: ' . \App\Models\NotificationSettings::where('pushover_enabled', true)->count() . PHP_EOL;
\$settings = \App\Models\NotificationSettings::where('pushover_enabled', true)->first();
if (\$settings) {
    echo 'First user ID: ' . \$settings->user_id . PHP_EOL;
    echo 'Has user key: ' . (!empty(\$settings->pushover_user_key) ? 'YES' : 'NO') . PHP_EOL;
    echo 'Has API token: ' . (!empty(\$settings->pushover_api_token) ? 'YES' : 'NO') . PHP_EOL;
}
"
```

### Step 5: Test Laravel Notification Service

```bash
# Test the actual notification service
php artisan tinker --execute="
\$user = \App\Models\User::whereHas('notificationSettings', function(\$q) {
    \$q->where('pushover_enabled', true);
})->first();

if (\$user) {
    \$service = app(\App\Services\NotificationService::class);
    try {
        \$service->sendTestPushover(\$user);
        echo 'SUCCESS: Test notification sent' . PHP_EOL;
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
    }
} else {
    echo 'No users with Pushover enabled found' . PHP_EOL;
}
"
```

### Step 6: Check Notification Logs

```bash
# View recent notification logs
php artisan notifications:logs --lines=20

# Filter for Pushover-specific logs
php artisan notifications:logs --filter="Pushover"

# Filter for errors only
php artisan notifications:logs --filter="error"
```

## Common Production Issues & Solutions

### Issue 1: Missing Environment Variables

**Symptoms:**
- "Pushover credentials not configured" error
- Environment variables show as "NOT SET"

**Solution:**
1. Add `PUSHOVER_USER_KEY` and `PUSHOVER_API_TOKEN` to production `.env`
2. Restart web server/PHP-FPM after changes
3. Clear Laravel config cache: `php artisan config:clear`

### Issue 2: Network Connectivity Problems

**Symptoms:**
- DNS resolution fails
- cURL/HTTPS tests fail
- "Connection timeout" or "Connection refused" errors

**Solutions:**
1. **Firewall Rules:** Ensure outbound HTTPS (port 443) is allowed
2. **DNS Issues:** Check `/etc/resolv.conf` or contact hosting provider
3. **SSL Problems:** Update CA certificates or check SSL configuration
4. **Proxy Settings:** Configure proxy if required by hosting environment

### Issue 3: Database Configuration Differences

**Symptoms:**
- No users found with Pushover enabled
- Different user IDs between local and production

**Solutions:**
1. **Export/Import Settings:** Export notification settings from local and import to production
2. **Create Production Settings:** Use the web interface to configure Pushover in production
3. **Database Migration:** Ensure all migrations have run in production

### Issue 4: Server Configuration Differences

**Symptoms:**
- Works locally but fails in production
- Different PHP versions or extensions

**Solutions:**
1. **PHP Extensions:** Ensure cURL and OpenSSL are installed
2. **PHP Version:** Check compatibility between local and production PHP versions
3. **File Permissions:** Ensure `storage/logs/` is writable
4. **Memory/Timeout:** Check PHP memory limits and execution timeouts

## Manual Testing Commands

### Test Pushover API Directly
```bash
curl -X POST https://api.pushover.net/1/messages.json \
  -d "token=YOUR_API_TOKEN" \
  -d "user=YOUR_USER_KEY" \
  -d "message=Manual production test" \
  -d "title=Production Test"
```

### Test Laravel HTTP Client
```bash
php artisan tinker --execute="
\$response = \Illuminate\Support\Facades\Http::asForm()->post('https://api.pushover.net/1/messages.json', [
    'token' => env('PUSHOVER_API_TOKEN'),
    'user' => env('PUSHOVER_USER_KEY'),
    'message' => 'Laravel HTTP test',
    'title' => 'Production Test'
]);
echo 'Status: ' . \$response->status() . PHP_EOL;
echo 'Body: ' . \$response->body() . PHP_EOL;
"
```

### Create Test Notification Settings
```bash
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$settings = \App\Models\NotificationSettings::updateOrCreate(
    ['user_id' => \$user->id],
    [
        'pushover_enabled' => true,
        'pushover_user_key' => env('PUSHOVER_USER_KEY'),
        'pushover_api_token' => env('PUSHOVER_API_TOKEN'),
    ]
);
echo 'Settings created for user: ' . \$user->id . PHP_EOL;
"
```

## Monitoring and Logging

### Enable Real-time Log Monitoring
```bash
# Watch notification logs in real-time
php artisan notifications:logs --follow

# Watch all Laravel logs
tail -f storage/logs/laravel.log
```

### Check System Logs
```bash
# Check system logs for network/DNS issues
sudo tail -f /var/log/syslog

# Check web server logs
sudo tail -f /var/log/nginx/error.log
# or
sudo tail -f /var/log/apache2/error.log
```

## Production Environment Checklist

Before deploying to production, ensure:

- [ ] `PUSHOVER_USER_KEY` is set in production `.env`
- [ ] `PUSHOVER_API_TOKEN` is set in production `.env`
- [ ] Production server can reach `api.pushover.net` (port 443)
- [ ] Firewall allows outbound HTTPS connections
- [ ] DNS resolution works for `api.pushover.net`
- [ ] PHP cURL and OpenSSL extensions are installed
- [ ] `storage/logs/` directory is writable
- [ ] Database migrations have been run
- [ ] At least one user has Pushover configured
- [ ] Queue worker is running (if using queues)
- [ ] Laravel config cache is cleared after env changes

## Getting Additional Help

If issues persist after following this guide:

1. **Collect Debug Output:** Run `production-pushover-debug.php` and save output
2. **Check Logs:** Gather recent notification logs and system logs
3. **Test Manually:** Use cURL commands to isolate the issue
4. **Contact Support:** Provide debug output, logs, and manual test results

## Quick Fix Commands

```bash
# Clear all Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart queue worker (if using queues)
php artisan queue:restart

# Test notification immediately
php artisan tinker --execute="
\$user = \App\Models\User::first();
if (\$user && \$user->notificationSettings) {
    app(\App\Services\NotificationService::class)->sendTestPushover(\$user);
    echo 'Test sent' . PHP_EOL;
}
"
```

The comprehensive logging in your NotificationService should provide detailed information about what's failing in production. Focus on the network connectivity and environment variable sections first, as these are the most common causes of local-vs-production differences.