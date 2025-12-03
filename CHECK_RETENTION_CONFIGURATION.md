# Check History Retention Configuration

## Overview

The application automatically deletes old check history data to prevent the database from growing indefinitely. By default, check history is retained for **30 days (1 month)**.

## How It Works

### Automatic Cleanup
- **Schedule**: Runs daily at 2:00 AM
- **Command**: `checks:prune`
- **Action**: Deletes all check records older than the configured retention period
- **Default Retention**: 30 days

### What Gets Deleted
- Check records where `checked_at` is older than the retention period
- All associated data (status, response time, error messages, etc.)

### What's Preserved
- All checks within the retention period
- Monitor configurations (never deleted by this process)
- User accounts and settings

## Configuration

### Environment Variable

Add or update this variable in your `.env` file:

```env
CHECK_RETENTION_DAYS=30
```

### Configuration Options

| Value | Behavior | Use Case |
|-------|----------|----------|
| `30` (default) | Keep 1 month of history | Recommended for most production environments |
| `7` | Keep 1 week of history | Low storage environments or high-frequency checks |
| `90` | Keep 3 months of history | Compliance or detailed historical analysis |
| `365` | Keep 1 year of history | Long-term trend analysis |
| `0` | Keep all history indefinitely | **Not recommended** - database will grow without limit |

### Updating the Configuration

1. **Edit your `.env` file:**
   ```bash
   # Open .env in your editor
   nano .env
   ```

2. **Add or update the retention setting:**
   ```env
   CHECK_RETENTION_DAYS=90
   ```

3. **Clear the configuration cache:**
   ```bash
   ddev artisan config:clear
   ```

4. **Verify the new setting:**
   ```bash
   ddev artisan tinker
   >>> config('monitoring.check_retention_days')
   => 90
   ```

## Manual Cleanup

You can manually run the cleanup command at any time:

### Using Default Configuration
```bash
ddev artisan checks:prune
```

### Override Retention Period (One-Time)
```bash
# Delete checks older than 7 days (ignores .env setting)
ddev artisan checks:prune --days=7

# Delete checks older than 90 days
ddev artisan checks:prune --days=90
```

### Dry Run (See What Would Be Deleted)
The command shows how many records will be deleted before actually deleting them:
```bash
ddev artisan checks:prune
# Output: Deleting checks older than 30 days (before 2025-11-03 02:00:00)...
# Output: Successfully deleted 12,450 check record(s).
```

## Monitoring the Cleanup

### Check Cleanup Logs
The cleanup command logs its activity. View logs with:
```bash
ddev artisan pail
```

### Verify Scheduled Task
Confirm the cleanup is scheduled:
```bash
ddev artisan schedule:list
```

You should see:
```
0 2 * * * checks:prune ........................... Next Due: 1 day from now
```

### Test the Schedule
Run all scheduled commands immediately (for testing):
```bash
ddev artisan schedule:run
```

## Database Impact

### Storage Estimates

With a 5-minute check interval (288 checks/day per monitor):

| Monitors | Days | Total Checks | Approx. Size |
|----------|------|--------------|--------------|
| 10 | 30 | 86,400 | ~10 MB |
| 50 | 30 | 432,000 | ~50 MB |
| 100 | 30 | 864,000 | ~100 MB |
| 10 | 365 | 1,051,200 | ~120 MB |
| 50 | 365 | 5,256,000 | ~600 MB |

### Performance Considerations

- **Recommended**: 30-90 days for most applications
- **High-frequency checks** (1-minute interval): Consider shorter retention (7-30 days)
- **Many monitors** (100+): Consider shorter retention or aggregate old data
- **Low-frequency checks** (30-minute interval): Can safely use longer retention (90-365 days)

## Troubleshooting

### Cleanup Not Running

1. **Verify scheduler is running:**
   ```bash
   ddev artisan schedule:list
   ```

2. **Check if scheduler heartbeat is working:**
   ```bash
   ddev artisan tinker
   >>> \App\Models\SchedulerHeartbeat::latest()->first()
   ```

3. **Manually run the cleanup:**
   ```bash
   ddev artisan checks:prune
   ```

### Database Still Growing

1. **Check current retention setting:**
   ```bash
   ddev artisan tinker
   >>> config('monitoring.check_retention_days')
   ```

2. **Verify .env file has the setting:**
   ```bash
   grep CHECK_RETENTION_DAYS .env
   ```

3. **Clear config cache:**
   ```bash
   ddev artisan config:clear
   ```

### Retention Set to 0

If `CHECK_RETENTION_DAYS=0`, no cleanup occurs. Update to a positive number:
```env
CHECK_RETENTION_DAYS=30
```

## Best Practices

1. **Start Conservative**: Begin with 30 days and adjust based on your needs
2. **Monitor Database Size**: Regularly check database growth
3. **Consider Your Use Case**:
   - **Compliance**: May require longer retention (90-365 days)
   - **Real-time Monitoring**: Shorter retention is fine (7-30 days)
   - **Trend Analysis**: Longer retention helps (90-180 days)
4. **Test Before Production**: Run manual cleanup to verify behavior
5. **Document Your Choice**: Note why you chose a specific retention period

## Advanced: Custom Retention Per Monitor

If you need different retention periods for different monitors, you could extend the system:

1. Add a `retention_days` column to the `monitors` table
2. Modify the `checks:prune` command to respect per-monitor settings
3. Fall back to global setting if monitor-specific setting is not defined

This is not currently implemented but can be added if needed.

## Related Commands

```bash
# View all scheduled tasks
ddev artisan schedule:list

# Run scheduler manually (for testing)
ddev artisan schedule:run

# View check count per monitor
ddev artisan tinker
>>> \App\Models\Monitor::withCount('checks')->get()

# View oldest check date
ddev artisan tinker
>>> \App\Models\Check::oldest('checked_at')->first()->checked_at

# View total check count
ddev artisan tinker
>>> \App\Models\Check::count()
```

## Support

If you need help configuring check retention:
1. Review this documentation
2. Check the `.env.example` file for reference
3. Test with manual cleanup first: `ddev artisan checks:prune --days=7`
4. Verify scheduler is running: `ddev artisan schedule:list`
