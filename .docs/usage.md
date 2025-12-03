# Usage Guide

This guide explains how to use the uptime monitoring application to monitor your websites and receive notifications.

## Table of Contents

- [Dashboard Overview](#dashboard-overview)
- [Managing Monitors](#managing-monitors)
- [Notification Settings](#notification-settings)
- [Understanding Check Results](#understanding-check-results)
- [Uptime Statistics](#uptime-statistics)
- [Best Practices](#best-practices)

## Dashboard Overview

After logging in, you'll see the dashboard displaying all your configured monitors.

### Monitor Status Indicators

Each monitor displays:
- **Name**: Friendly name you assigned
- **URL**: The endpoint being monitored
- **Status**: Current availability state
  - 🟢 **Up**: Monitor is responding successfully (HTTP 2xx)
  - 🔴 **Down**: Monitor is not responding or returning errors (HTTP 4xx/5xx or timeout)
  - 🟡 **Pending**: Monitor is newly created and awaiting first check
- **Last Checked**: Timestamp of the most recent check
- **Response Time**: How long the last successful check took (in milliseconds)

### Monitor Ordering

Monitors are automatically ordered by priority:
1. **Down monitors** appear first (requires immediate attention)
2. **Up monitors** appear next
3. **Pending monitors** appear last

## Managing Monitors

### Adding a New Monitor

1. Click the **"Add Monitor"** button on the dashboard
2. Fill in the monitor details:
   - **Name**: A friendly name to identify this monitor (e.g., "Production Website")
   - **URL**: The full URL to monitor (must start with `http://` or `https://`)
   - **Check Interval**: How often to check this URL (in minutes, 1-1440)
3. Click **"Create Monitor"**

**Example:**
```
Name: Company Homepage
URL: https://example.com
Check Interval: 5 minutes
```

#### URL Validation Rules

The system validates URLs to ensure they are valid:
- ✅ Must use HTTP or HTTPS protocol
- ✅ Can include ports: `https://example.com:8080`
- ✅ Can include paths: `https://example.com/api/health`
- ✅ Can include query parameters: `https://example.com/status?check=1`
- ✅ Supports internationalized domain names
- ❌ Cannot use localhost addresses
- ❌ Cannot use IP addresses without proper domains
- ❌ Must have a valid top-level domain

### Viewing Monitor Details

Click on any monitor card to view detailed information:
- **Current Status**: Up, Down, or Pending
- **URL**: The monitored endpoint
- **Check Interval**: How often checks are performed
- **Uptime Statistics**: 24-hour, 7-day, and 30-day uptime percentages
- **Check History**: Recent check results with timestamps, status codes, and response times

### Editing a Monitor

1. Click on a monitor to view its details
2. Click the **"Edit"** button
3. Modify the name, URL, or check interval
4. Click **"Update Monitor"**

**Note:** Editing a monitor preserves all historical check data.

### Deleting a Monitor

1. Click on a monitor to view its details
2. Click the **"Delete"** button
3. Confirm the deletion in the dialog

**Warning:** Deleting a monitor permanently removes it and all associated check history. This action cannot be undone.

## Notification Settings

Configure how you want to be notified when monitor status changes.

### Accessing Notification Settings

1. Click your name in the top navigation
2. Select **"Notification Settings"**

### Email Notifications

Email notifications are sent when a monitor transitions between up and down states.

**To enable email notifications:**
1. Check **"Enable Email Notifications"**
2. Enter your email address
3. Click **"Save Settings"**

**Email notification content includes:**
- Monitor name
- Monitored URL
- Status change (Up → Down or Down → Up)
- Timestamp of the status change
- For down notifications: Error details or HTTP status code
- For recovery notifications: Duration of the downtime

**Example down notification:**
```
Subject: Monitor Down: Company Homepage

Your monitor "Company Homepage" is now DOWN.

URL: https://example.com
Status: Down
Time: 2024-12-02 14:30:00 UTC
Error: HTTP 503 Service Unavailable
```

**Example recovery notification:**
```
Subject: Monitor Recovered: Company Homepage

Your monitor "Company Homepage" has RECOVERED.

URL: https://example.com
Status: Up
Time: 2024-12-02 14:35:00 UTC
Downtime Duration: 5 minutes
```

### Pushover Notifications

Pushover sends instant push notifications to your mobile device.

**To enable Pushover notifications:**

1. Sign up for Pushover at <https://pushover.net>
2. Install the Pushover app on your mobile device
3. Create an application in Pushover to get your API token
4. In the application's notification settings:
   - Check **"Enable Pushover Notifications"**
   - Enter your **User Key** (from your Pushover dashboard)
   - Enter your **API Token** (from your Pushover application)
5. Click **"Save Settings"**

**Pushover notification priorities:**
- **High Priority (2)**: Sent when a monitor goes down (bypasses quiet hours)
- **Normal Priority (0)**: Sent when a monitor recovers

### Notification Behavior

**Important rules:**
- Notifications are only sent when status **changes** (up → down or down → up)
- No duplicate notifications are sent for consecutive checks with the same status
- The first check of a new monitor does not trigger a notification
- If notification delivery fails, it's logged but doesn't block monitoring

## Understanding Check Results

### Check History

The check history table shows recent checks for a monitor:

| Timestamp | Status | Status Code | Response Time | Error |
|-----------|--------|-------------|---------------|-------|
| 2024-12-02 14:35:00 | Success | 200 | 145ms | - |
| 2024-12-02 14:30:00 | Failed | 503 | - | Service Unavailable |
| 2024-12-02 14:25:00 | Success | 200 | 132ms | - |

### Status Codes

**Success (2xx):**
- 200 OK
- 201 Created
- 204 No Content
- Any status code in the 200-299 range

**Failure (4xx/5xx):**
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 500 Internal Server Error
- 502 Bad Gateway
- 503 Service Unavailable
- Any status code in the 400-599 range

### Timeout Failures

If a monitored URL doesn't respond within 30 seconds, the check is marked as failed with a timeout error:

```
Error: Connection timeout after 30 seconds
```

### Common Error Messages

- **Connection timeout**: The server didn't respond within 30 seconds
- **Unable to resolve hostname**: DNS lookup failed for the domain
- **SSL certificate validation failed**: HTTPS certificate is invalid or expired
- **Connection refused**: The server actively refused the connection
- **Network unreachable**: Network connectivity issue

## Uptime Statistics

Uptime statistics show the reliability of your monitors over different time periods.

### Calculation Method

Uptime percentage is calculated as:

```
Uptime % = (Successful Checks / Total Checks) × 100
```

### Time Periods

- **24 Hours**: Uptime over the last 24 hours
- **7 Days**: Uptime over the last 7 days
- **30 Days**: Uptime over the last 30 days

### Interpreting Uptime

- **99.9%+**: Excellent uptime (less than 43 minutes downtime per month)
- **99.0-99.9%**: Good uptime (43 minutes to 7 hours downtime per month)
- **95.0-99.0%**: Fair uptime (7 hours to 1.5 days downtime per month)
- **Below 95%**: Poor uptime (more than 1.5 days downtime per month)

### No Data Available

If a monitor is newly created or has no checks in a time period, uptime will show as "N/A".

## Best Practices

### Choosing Check Intervals

**Recommended intervals:**
- **Critical services**: 1-5 minutes
- **Important services**: 5-15 minutes
- **Standard monitoring**: 15-30 minutes
- **Low-priority services**: 30-60 minutes

**Considerations:**
- More frequent checks provide faster detection but generate more data
- Less frequent checks reduce load but may miss brief outages
- Balance between detection speed and resource usage

### URL Selection

**Best practices:**
- Monitor health check endpoints when available (e.g., `/health`, `/status`)
- Use lightweight endpoints that don't trigger heavy processing
- Monitor the actual user-facing URL if no health endpoint exists
- Include query parameters if needed for authentication or routing

**Examples:**
```
✅ https://api.example.com/health
✅ https://example.com/
✅ https://example.com/api/v1/status
❌ https://example.com/admin/dashboard (requires authentication)
❌ https://example.com/heavy-report (slow, resource-intensive)
```

### Notification Management

**Tips:**
- Enable both email and Pushover for critical monitors
- Use email for detailed information and record-keeping
- Use Pushover for immediate mobile alerts
- Test notifications after setup to ensure they work
- Keep your notification email address up to date

### Monitor Organization

**Naming conventions:**
- Use descriptive names: "Production API" instead of "API"
- Include environment: "Staging Website" vs "Production Website"
- Group related services: "Payment Gateway - Stripe", "Payment Gateway - PayPal"

**Examples:**
```
✅ Production Website - Homepage
✅ Staging API - Health Check
✅ Payment Gateway - Stripe
❌ Website
❌ API
❌ Monitor 1
```

### Monitoring Strategy

**What to monitor:**
- Public-facing websites and landing pages
- API endpoints used by applications
- Critical backend services with health endpoints
- Third-party integrations and webhooks
- CDN endpoints and asset delivery

**What not to monitor:**
- Internal services not accessible from the internet
- Authenticated endpoints (will always fail)
- Rate-limited endpoints (may trigger false alarms)
- Development or local environments

### Responding to Alerts

**When you receive a down notification:**

1. **Verify the issue**: Check if the service is actually down
2. **Check the error**: Review the error message in the notification
3. **Investigate**: Look at server logs, hosting provider status, etc.
4. **Take action**: Restart services, fix issues, or contact support
5. **Monitor recovery**: Wait for the recovery notification

**False positives:**
- Temporary network issues may cause brief failures
- Scheduled maintenance should be noted
- Consider increasing check interval if false positives are frequent

### Maintenance Windows

**During scheduled maintenance:**
1. Temporarily increase the check interval
2. Or temporarily disable notifications (edit settings)
3. Re-enable normal settings after maintenance

**Note:** The application doesn't currently support maintenance windows, so manual adjustment is required.

## Troubleshooting

### Monitor Stuck in Pending

**Problem:** Monitor shows "Pending" status for a long time.

**Solution:**
- Ensure the queue worker is running
- Check that the scheduler is configured (production)
- Manually trigger a check: `ddev artisan schedule:run`

### Not Receiving Notifications

**Problem:** Status changes but no notifications arrive.

**Solution:**
- Verify notification settings are enabled
- Check email address is correct
- Test email configuration (see Setup Guide)
- Check spam folder for email notifications
- Verify Pushover credentials are correct

### Incorrect Uptime Statistics

**Problem:** Uptime percentage seems wrong.

**Solution:**
- Uptime is calculated from actual check results
- Recent status changes may not be reflected immediately
- Check the check history to verify the calculation
- Remember: Uptime = (Successful Checks / Total Checks) × 100

### Monitor Shows Down But Service is Up

**Problem:** Monitor reports down but the service is accessible.

**Solution:**
- Check if the URL is correct
- Verify the service returns HTTP 2xx status codes
- Ensure the service responds within 30 seconds
- Check for IP blocking or rate limiting
- Review the error message in check history

## Advanced Usage

### API Health Checks

For APIs, create a dedicated health check endpoint:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

Monitor this endpoint instead of functional API routes.

### Multiple Environments

Create separate monitors for each environment:

```
Production Website - https://example.com
Staging Website - https://staging.example.com
Development Website - https://dev.example.com
```

Use different check intervals:
- Production: 5 minutes
- Staging: 15 minutes
- Development: 30 minutes

### Monitoring Third-Party Services

Monitor critical third-party dependencies:

```
Stripe API - https://api.stripe.com/healthcheck
SendGrid API - https://api.sendgrid.com/v3/health
AWS S3 - https://your-bucket.s3.amazonaws.com/health.txt
```

## Getting Help

If you encounter issues not covered in this guide:

1. Check the application logs: `ddev artisan pail`
2. Review the [Setup Guide](setup.md) for configuration issues
3. Check the [Troubleshooting section](setup.md#troubleshooting) in the Setup Guide
4. Contact your system administrator or development team

## Summary

You now know how to:
- ✅ Add and manage monitors
- ✅ Configure email and Pushover notifications
- ✅ Interpret check results and uptime statistics
- ✅ Follow best practices for effective monitoring
- ✅ Troubleshoot common issues

Happy monitoring! 🚀
