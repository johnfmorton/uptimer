# Notification Debugging Guide

This guide helps you debug email and Pushover notification issues in production environments.

## Enhanced Logging Features

The notification system now includes comprehensive logging to help diagnose production issues:

### Dedicated Notification Log File

All notification-related logs are now written to a separate log file: `storage/logs/notifications.log`

This makes it easier to focus on notification issues without sifting through general application logs.

### View Notification Logs

Use the custom artisan command to view notification logs:

```bash
# View last 50 lines of notification logs
ddev artisan notifications:logs

# View last 100 lines
ddev artisan notifications:logs --lines=100

# Follow logs in real-time
ddev artisan notifications:logs --follow

# Filter for errors only
ddev artisan notifications:logs --filter="error"

# Filter for Pushover logs only
ddev artisan notifications:logs --filter="Pushover"

# Filter for email logs only
ddev artisan notifications:logs --filter="email"
```

## What Gets Logged

### Email Notifications

**Before sending:**
- Mail driver configuration (SMTP, host, port)
- Authentication status (username/password configured)
- Encryption settings
- From address and name
- Target email address

**After sending:**
- Success confirmation with template used
- Detailed error information on failure
- Troubleshooting hints for common issues

**On failure:**
- Exception class and message
- Mail configuration details
- Specific troubleshooting steps

### Pushover Notifications

**Before sending:**
- Masked API credentials (first 8 and last 4 characters)
- Message content and priority
- Payload structure

**After API call:**
- HTTP status code
- Full API response body
- Response headers
- Success/failure status

**On failure:**
- Detailed error response from Pushover API
- Network-level errors (DNS, SSL, connectivity)
- Troubleshooting hints for common issues

### Test Notifications

**Job-level logging:**
- Job ID and queue information
- Configuration validation before sending
- Retry attempt information
- Permanent failure logging

## Common Production Issues & Solutions

### Email Issues

#### 1. SMTP Connection Failures

**Symptoms in logs:**
```
Connection refused
Connection timeout
```

**Check:**
- `MAIL_HOST` and `MAIL_PORT` in `.env`
- Firewall rules allowing outbound SMTP connections
- Network connectivity: `telnet smtp.server.com 587`

#### 2. Authentication Failures

**Symptoms in logs:**
```
Authentication failed
Invalid credentials
```

**Check:**
- `MAIL_USERNAME` and `MAIL_PASSWORD` in `.env`
- SMTP server requires authentication
- App-specific passwords (Gmail, Outlook)

#### 3. SSL/TLS Issues

**Symptoms in logs:**
```
SSL certificate verification failed
TLS handshake failed
```

**Check:**
- `MAIL_ENCRYPTION` setting (tls, ssl, null)
- Server SSL certificate validity
- PHP OpenSSL extension enabled

#### 4. Rate Limiting

**Symptoms in logs:**
```
Too many connections
Rate limit exceeded
```

**Solutions:**
- Implement email queuing with delays
- Use dedicated SMTP service (SendGrid, Mailgun)
- Check provider rate limits

### Pushover Issues

#### 1. Invalid API Credentials

**Symptoms in logs:**
```
HTTP 400 - user key is invalid
HTTP 400 - application token is invalid
```

**Check:**
- Pushover user key (30 characters)
- Pushover API token (30 characters)
- Account status and limits

#### 2. Network Connectivity

**Symptoms in logs:**
```
Connection timeout
DNS resolution failed
SSL certificate validation failed
```

**Debug commands:**
```bash
# Test DNS resolution
nslookup api.pushover.net

# Test HTTPS connectivity
curl -I https://api.pushover.net

# Test with credentials
curl -X POST https://api.pushover.net/1/messages.json \
  -d "token=YOUR_TOKEN" \
  -d "user=YOUR_USER_KEY" \
  -d "message=Test message"
```

#### 3. Message Limits

**Symptoms in logs:**
```
HTTP 429 - message limit exceeded
```

**Solutions:**
- Check Pushover account message limits
- Implement message throttling
- Upgrade Pushover account if needed

## Debugging Workflow

### 1. Check Configuration

```bash
# View current mail configuration
ddev artisan tinker
>>> config('mail')

# View Pushover configuration
>>> config('services.pushover')
```

### 2. Test Notifications

```bash
# Send test email
# Use the web interface or trigger via API

# Send test Pushover
# Use the web interface or trigger via API
```

### 3. Monitor Logs

```bash
# Watch logs in real-time
ddev artisan notifications:logs --follow

# Check for errors
ddev artisan notifications:logs --filter="error"
```

### 4. Verify External Services

**For Email:**
```bash
# Test SMTP connection
telnet your-smtp-server.com 587

# Test with openssl for TLS
openssl s_client -connect your-smtp-server.com:587 -starttls smtp
```

**For Pushover:**
```bash
# Test API connectivity
curl -I https://api.pushover.net

# Test with credentials
curl -X POST https://api.pushover.net/1/messages.json \
  -d "token=YOUR_TOKEN" \
  -d "user=YOUR_USER_KEY" \
  -d "message=Production test"
```

## Production Environment Checklist

### Email Setup
- [ ] SMTP server accessible from production server
- [ ] Correct MAIL_* environment variables
- [ ] Firewall allows outbound SMTP connections (ports 25, 587, 465)
- [ ] DNS resolution working for mail server
- [ ] SSL certificates valid and trusted
- [ ] Authentication credentials correct
- [ ] Rate limits understood and configured

### Pushover Setup
- [ ] Production server can reach api.pushover.net (port 443)
- [ ] Pushover API credentials valid
- [ ] User key belongs to correct account
- [ ] Message limits sufficient for usage
- [ ] Firewall allows outbound HTTPS connections
- [ ] DNS resolution working for api.pushover.net

### Laravel Configuration
- [ ] Queue worker running (`ddev artisan queue:work`)
- [ ] Notification settings properly configured in database
- [ ] Log files writable (`storage/logs/`)
- [ ] Environment variables loaded correctly
- [ ] Cache cleared after configuration changes

## Log Analysis Examples

### Successful Email
```
[2024-01-07 10:30:15] notifications.INFO: Attempting to send email notification
[2024-01-07 10:30:16] notifications.INFO: Email notification sent successfully
```

### Failed Email with Details
```
[2024-01-07 10:30:15] notifications.ERROR: Email notification failed to send {
  "error": "Connection refused",
  "mail_host": "smtp.example.com",
  "mail_port": 587,
  "troubleshooting_hints": [
    "Check MAIL_* environment variables in .env file",
    "Verify SMTP server credentials are correct"
  ]
}
```

### Successful Pushover
```
[2024-01-07 10:30:15] notifications.INFO: Attempting to send Pushover notification
[2024-01-07 10:30:16] notifications.INFO: Pushover API response received {"status_code": 200}
[2024-01-07 10:30:16] notifications.INFO: Pushover notification sent successfully
```

### Failed Pushover with Details
```
[2024-01-07 10:30:15] notifications.ERROR: Pushover notification failed {
  "status_code": 400,
  "response_body": "{\"user\":\"invalid\",\"errors\":[\"user key is invalid\"]}",
  "troubleshooting_hints": [
    "Verify Pushover user key is correct (30 characters)",
    "Check Pushover API token is valid and active"
  ]
}
```

## Getting Help

If you're still experiencing issues after following this guide:

1. **Collect logs**: Use `ddev artisan notifications:logs --lines=200` to get recent logs
2. **Test manually**: Use curl commands to test external services directly
3. **Check service status**: Verify SMTP provider and Pushover service status
4. **Review configuration**: Double-check all environment variables and settings

The enhanced logging should provide enough detail to identify and resolve most notification issues in production environments.