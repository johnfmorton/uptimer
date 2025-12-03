# Check History Display - Explanation & Solutions

## Issues Identified

### 1. Inaccurate "1 hour ago" Display
**Problem:** Everything between ~48 minutes and 1 hour 59 minutes was showing as "1 hour ago"

**Root Cause:** Carbon's `diffForHumans()` method uses threshold-based rounding:
- 0-59 minutes: Shows minutes
- 60-119 minutes: Shows "1 hour ago" 
- 120+ minutes: Shows "2 hours ago", etc.

**Solution Implemented:**
Created a custom time display that shows more granular information:
- Under 1 minute: "Just now"
- 1-59 minutes: "X minutes ago"
- 1-24 hours: "X hours, Y minutes ago" (e.g., "1 hour, 23 minutes ago")
- Over 24 hours: Falls back to Carbon's `diffForHumans()`

This provides much more accurate relative timestamps for recent checks.

### 2. Check History List Management

**Current Implementation:**
- **Pagination:** 50 checks per page (line 82 in `MonitorController.php`)
- **Ordering:** Most recent first (`orderBy('checked_at', 'desc')`)
- **Storage:** All checks are stored indefinitely in the database

**What This Means:**
- With a 5-minute check interval: 50 checks = ~4 hours of history per page
- With a 1-minute check interval: 50 checks = ~50 minutes per page
- Pagination links appear at the bottom to navigate to older checks
- No automatic cleanup/pruning of old checks

## ✅ Implemented: Automatic Data Retention Policy

**Status**: Implemented and active

The application now automatically deletes check history older than 30 days (configurable).

### How It Works
- **Schedule**: Runs daily at 2:00 AM
- **Command**: `ddev artisan checks:prune`
- **Default Retention**: 30 days (configurable via `CHECK_RETENTION_DAYS` in `.env`)
- **Manual Execution**: `ddev artisan checks:prune --days=7`

### Configuration
Set in your `.env` file:
```env
CHECK_RETENTION_DAYS=30  # Keep 30 days of history (default)
```

See [CHECK_RETENTION_CONFIGURATION.md](CHECK_RETENTION_CONFIGURATION.md) for complete documentation.

### Additional Options (Not Yet Implemented)

#### Option 2: Increase Items Per Page
Change line 82 in `MonitorController.php`:
```php
$checks = $monitor->checks()
    ->orderBy('checked_at', 'desc')
    ->paginate(100); // Show more checks per page
```

#### Option 3: Add Time-Based Filtering
Add tabs or filters to show:
- Last hour
- Last 24 hours
- Last 7 days
- All history

#### Option 4: Aggregate Old Data
Keep detailed checks for recent history, aggregate older checks:
- Last 24 hours: Keep all checks
- 1-7 days: Keep hourly summaries
- 7-30 days: Keep daily summaries
- 30+ days: Delete or keep monthly summaries

## Display Improvements Implemented

### Before:
```
2025-12-03 18:58:01
1 hour ago

2025-12-03 18:52:03
1 hour ago

2025-12-03 18:46:30
1 hour ago
```

### After:
```
2025-12-03 18:58:01
58 minutes ago

2025-12-03 18:52:03
1 hour, 4 minutes ago

2025-12-03 18:46:30
1 hour, 10 minutes ago
```

## Files Modified

1. **resources/views/monitors/show.blade.php** (lines 218-240)
   - Replaced `diffForHumans()` with custom time calculation
   - Shows more granular time differences for recent checks

## Testing the Changes

To see the improvements:
1. View any monitor detail page with check history
2. Look at the "Check History" section
3. Verify that timestamps now show accurate relative times
4. Check that pagination works correctly at the bottom of the table

## Future Considerations

1. **Performance:** With thousands of checks, consider adding database indexes on `checked_at`
2. **Data Retention:** Implement a cleanup strategy for old checks
3. **UI Enhancement:** Consider adding a visual timeline or chart for check history
4. **Real-time Updates:** Add WebSocket or polling to update the check history live
5. **Export:** Allow users to export check history as CSV/JSON
