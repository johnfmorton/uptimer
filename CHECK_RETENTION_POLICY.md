# Check History Data Retention Policy

## Overview

The application automatically deletes old check history data to prevent the database from growing indefinitely. By default, check history is retained for **30 days** (1 month).

## How It Works

### Automatic Cleanup
- **Schedule:** Runs daily at 2:00 AM
- **Command:** `checks:prune`
- **Action:** Deletes all check records older than the configured retention period
- **Impact:** Reduces database size and improves query performance

### What Gets Deleted
- Individual check records (success/failure status, response times, status codes)
- Only checks older than the retention period are deleted
- Monitors themselves are NOT deleted
- Current monitor status is NOT affected

### What Is Preserved
- All monitors and their configurations
- Current monitor status (up/down)
- Notification settings
- User accounts and preferences

## Configuration

### Environment Variable

The retention period is controlled by the `CHECK_RETENTION_DAYS` environment variable in your `.env` file.

**Default:** 30 days (1 month)

### Changing the Retention Period

1. Open your `.env` file
2. Find or add the `CHECK_RETENTION_DAYS` setting
3. Set it to your desired number of days:

```env
# Keep 7 days of history
CHECK_RETENTION_DAYS=7

# Keep 90 days of history (3 months)
CHECK_RETENTION_DAYS=90

# Keep 365 days of history (1 year)
CHECK_RETENTION_DAYS=365

# Keep all history indefinitely (not recommended)
CHECK_RETENTION_DAYS=0
```

4. Save the file
5. Clear the configuration cache:
```bash
ddev artisan config:clear
```

### Recommended Retention Periods

| Use Case | Recommended Days | Storage Impact |
|----------|-----------------|----------------|
| Personal/Small Projects | 7-14 days | Minimal |
| Small Business | 30 days (default) | Low |
| Enterprise/Compliance | 90-365 days | Moderate to High |
| Unlimited History | 0 (not recommended) | Grows indefinitely |

### Storage Considerations

**Example:** A monitor checking every 5 minutes generates:
- ~288 checks per day
- ~8,640 checks per month
- ~103,680 checks per year

With 10 monitors:
- 30 days: ~86,400 check records
- 90 days: ~259,200 check records
- 365 days: ~1,036,800 check records

## Manual Cleanup

### Run Cleanup Immediately

To manually trigger the cleanup process:

```bash
ddev artisan checks:prune
```

### Dry Run (Preview What Would Be Deleted)

To see what would be deleted without actually deleting:

```bash
ddev artisan checks:prune --dry-run
```

This will show:
- How many checks would be deleted
- Sample of the oldest checks that would be removed (up to 5 records)
- No actual deletion occurs

### Override Retention Period

To use a different retention period for a single run:

```bash
# Delete checks older than 7 days (ignores config)
ddev artisan checks:prune --days=7

# Delete checks older than 90 days
ddev artisan checks:prune --days=90

# Preview with custom retention
ddev artisan checks:prune --days=7 --dry-run
```

### Example Output

```
Deleting checks older than 30 days (before 2025-11-03 02:00:00)...
Successfully deleted 12,543 check record(s).
```

## Scheduler Setup

The cleanup command runs automatically via Laravel's task scheduler. Ensure the scheduler is running:

### DDEV Environment (Development)

The scheduler runs automatically in DDEV when you use:

```bash
ddev composer dev
```

Or manually start it:

```bash
ddev artisan schedule:work
```

### Production Environment

Add this cron entry to run the scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use a process manager like Supervisor to run:

```bash
php artisan schedule:work
```

## Monitoring the Cleanup

### Check Scheduler Status

View the scheduler status in the application dashboard or run:

```bash
ddev artisan schedule:list
```

This shows all scheduled tasks including:
- `checks:prune` - Daily at 2:00 AM

### View Logs

Cleanup activity is logged. Check the logs:

```bash
ddev artisan pail
```

Or view log files directly:

```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Cleanup Not Running

1. **Verify scheduler is running:**
   ```bash
   ddev artisan schedule:list
   ```

2. **Check configuration:**
   ```bash
   ddev artisan config:show monitoring
   ```
   Look for `check_retention_days` value

3. **Run manually to test:**
   ```bash
   ddev artisan checks:prune --dry-run
   ```

4. **Clear config cache:**
   ```bash
   ddev artisan config:clear
   ```

### Database Still Growing

1. **Check retention setting:**
   - Ensure `CHECK_RETENTION_DAYS` is set in `.env`
   - Verify it's not set to `0` (unlimited)

2. **Verify scheduler is running:**
   - In development: `ddev composer dev` should be running
   - In production: Cron job or `schedule:work` should be active

3. **Check for errors:**
   ```bash
   ddev artisan checks:prune
   ```
   Look for any error messages

### Need to Keep More History

If you need to temporarily keep more history:

1. Update `.env`:
   ```env
   CHECK_RETENTION_DAYS=90
   ```

2. Clear config cache:
   ```bash
   ddev artisan config:clear
   ```

3. The next scheduled cleanup will use the new retention period

## Database Maintenance

### Check Database Size

To see how much space checks are using:

```bash
ddev mysql -e "SELECT 
    COUNT(*) as total_checks,
    MIN(checked_at) as oldest_check,
    MAX(checked_at) as newest_check
FROM checks;"
```

### Manual Database Cleanup

If you need to aggressively clean up the database:

```bash
# Delete checks older than 7 days (regardless of config)
ddev artisan tinker
>>> App\Models\Check::where('checked_at', '<', now()->subDays(7))->delete();
```

**Warning:** This bypasses the configured retention period. Use with caution.

## Best Practices

1. **Set appropriate retention:** Balance between historical data needs and storage costs
2. **Monitor database size:** Regularly check database growth
3. **Test before production:** Use `--dry-run` to verify cleanup behavior
4. **Document changes:** If you change retention, document why and when
5. **Consider compliance:** Some industries require specific data retention periods
6. **Regular backups:** Always maintain database backups before major cleanups

## FAQ

**Q: Will this delete my monitors?**  
A: No, only check history records are deleted. Monitors remain intact.

**Q: Can I recover deleted checks?**  
A: No, deleted checks are permanently removed. Ensure you have backups if needed.

**Q: What happens if I set retention to 0?**  
A: No checks will be deleted automatically. Database will grow indefinitely.

**Q: Can I run cleanup more frequently?**  
A: Yes, edit `routes/console.php` and change `dailyAt('02:00')` to `hourly()` or another schedule.

**Q: Does this affect monitor status?**  
A: No, current monitor status is independent of check history.

**Q: What if cleanup fails?**  
A: Check logs for errors. The command will retry on the next scheduled run.

## Related Documentation

- [CHECK_HISTORY_EXPLANATION.md](CHECK_HISTORY_EXPLANATION.md) - Check history display and pagination
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - General troubleshooting guide
- [README.md](README.md) - Main project documentation
