# Check History Automatic Cleanup - Implementation Summary

## What Was Implemented

An automatic cleanup system for check history data that runs daily to prevent database growth.

## Key Features

1. **Automatic Daily Cleanup**
   - Runs every day at 2:00 AM via Laravel scheduler
   - Deletes check records older than configured retention period
   - Default retention: 30 days (configurable via `.env`)

2. **Manual Cleanup Command**
   - Command: `ddev artisan checks:prune`
   - Supports dry-run mode: `ddev artisan checks:prune --dry-run`
   - Supports custom retention: `ddev artisan checks:prune --days=7`

3. **Configuration**
   - Environment variable: `CHECK_RETENTION_DAYS`
   - Default value: 30 days
   - Set to 0 to keep all history indefinitely

## Files Modified/Created

### Created
- `CHECK_RETENTION_POLICY.md` - Comprehensive documentation
- `tests/Feature/PruneOldChecksCommandTest.php` - Test suite (6 tests, all passing)
- `IMPLEMENTATION_SUMMARY.md` - This file

### Modified
- `app/Console/Commands/PruneCheckHistory.php` - Added dry-run feature
- `config/monitoring.php` - Already had retention configuration
- `.env.example` - Already documented
- `routes/console.php` - Already scheduled
- `README.md` - Fixed documentation link

## Configuration

### .env Variable
```env
# Default: 30 days
CHECK_RETENTION_DAYS=30

# Keep 7 days
CHECK_RETENTION_DAYS=7

# Keep 90 days
CHECK_RETENTION_DAYS=90

# Keep all history (not recommended)
CHECK_RETENTION_DAYS=0
```

### Changing Retention Period
1. Edit `.env` file
2. Update `CHECK_RETENTION_DAYS` value
3. Run: `ddev artisan config:clear`

## Usage

### Manual Cleanup
```bash
# Run cleanup now
ddev artisan checks:prune

# Preview what would be deleted
ddev artisan checks:prune --dry-run

# Use custom retention period
ddev artisan checks:prune --days=7

# Combine options
ddev artisan checks:prune --days=7 --dry-run
```

### Verify Schedule
```bash
ddev artisan schedule:list
```

Output shows:
```
0 2 * * *  php artisan checks:prune .... Next Due: X hours from now
```

## Testing

All tests pass:
```bash
ddev artisan test --filter=PruneOldChecksCommandTest
```

Test coverage:
- ✓ Prunes old checks based on retention period
- ✓ No checks deleted when none are old enough
- ✓ Custom days option overrides config
- ✓ Dry run mode doesn't delete checks
- ✓ Retention period of 0 keeps all checks
- ✓ Custom retention period works correctly

## Documentation

Complete documentation available in:
- `CHECK_RETENTION_POLICY.md` - Full guide with examples, troubleshooting, FAQ
- `README.md` - Quick reference with link to detailed docs
- `.env.example` - Configuration comments

## Scheduler Requirements

The cleanup runs automatically via Laravel's scheduler. Ensure the scheduler is running:

### Development (DDEV)
```bash
ddev composer dev
```
This starts the scheduler along with other services.

### Production
Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use a process manager to run:
```bash
php artisan schedule:work
```

## Storage Impact Examples

With default 30-day retention:

| Monitors | Check Interval | Checks/Day | 30-Day Total |
|----------|---------------|------------|--------------|
| 1        | 5 minutes     | 288        | ~8,640       |
| 10       | 5 minutes     | 2,880      | ~86,400      |
| 50       | 5 minutes     | 14,400     | ~432,000     |

## What Gets Deleted

- ✓ Individual check records (status, response time, status code)
- ✗ Monitors (preserved)
- ✗ Current monitor status (preserved)
- ✗ Notification settings (preserved)
- ✗ User accounts (preserved)

## Next Steps

1. Monitor the cleanup in production logs
2. Adjust retention period based on needs
3. Consider database backups before first cleanup
4. Review storage usage after cleanup runs

## Support

For issues or questions:
- See `CHECK_RETENTION_POLICY.md` for troubleshooting
- Check logs: `ddev artisan pail`
- Test manually: `ddev artisan checks:prune --dry-run`
