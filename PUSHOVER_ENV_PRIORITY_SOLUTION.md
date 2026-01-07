# Pushover Environment Priority Solution

## Problem Solved

Previously, Pushover credentials were stored only in the database per user, and updating `.env` variables required running a manual sync script (`sync-env-to-database.php`). This was inconvenient and error-prone in production.

## New Solution: Automatic Environment Priority

The application now automatically prioritizes `.env` variables over database values when they're available. **No manual sync scripts needed!**

## How It Works

### 1. Automatic Priority System

The `NotificationSettings` model now includes methods that automatically check for environment variables first:

- `getEffectivePushoverUserKey()` - Returns env var if set, otherwise database value
- `getEffectivePushoverApiToken()` - Returns env var if set, otherwise database value  
- `isPushoverEffectivelyEnabled()` - Checks if Pushover is enabled AND has valid credentials
- `getPushoverCredentialSources()` - Shows where credentials are coming from (for debugging)

### 2. Environment Variable Requirements

For environment variables to be used, they must:
- Be set in `.env` file as `PUSHOVER_USER_KEY` and `PUSHOVER_API_TOKEN`
- Be exactly 30 characters long (standard Pushover credential length)
- Both must be present (if only one is set, database values are used)

### 3. Fallback Behavior

- If `.env` credentials are valid → Use them for ALL users
- If `.env` credentials are missing/invalid → Use individual database credentials
- Users still need Pushover enabled in their notification settings

## Benefits

✅ **Set once, works everywhere**: Configure credentials in `.env` and all users automatically use them  
✅ **No manual sync**: Environment changes take effect immediately  
✅ **Backward compatible**: Existing database credentials still work as fallback  
✅ **Clear debugging**: Logs show exactly where credentials are coming from  
✅ **Production friendly**: Update `.env` and restart - no scripts to run  

## Usage

### For Development
```bash
# Add to .env file
PUSHOVER_USER_KEY=your_30_character_user_key_here
PUSHOVER_API_TOKEN=your_30_character_api_token_here

# Test the system
ddev artisan pushover:test
```

### For Production
1. Update `.env` with your Pushover credentials
2. Restart your application (to reload environment variables)
3. All users with Pushover enabled will automatically use the new credentials

### Testing Commands

```bash
# Test all users with Pushover enabled
ddev artisan pushover:test

# Test specific user
ddev artisan pushover:test --user-id=1

# Run the diagnostic script
ddev exec php test-env-priority-system.php
```

## Migration from Old System

If you were using the old sync scripts:

1. **Keep your existing database credentials** - they work as fallback
2. **Add credentials to `.env`** - they'll automatically take priority
3. **Remove sync scripts** - no longer needed
4. **Test with `ddev artisan pushover:test`**

## Files Modified

- `app/Models/NotificationSettings.php` - Added environment priority methods
- `app/Services/NotificationService.php` - Updated to use new methods
- `app/Console/Commands/TestPushoverCredentials.php` - New testing command
- `test-env-priority-system.php` - Diagnostic script

## Logging Improvements

The system now logs credential sources in all Pushover operations:

```json
{
  "credential_sources": {
    "user_key_source": "environment",
    "api_token_source": "environment", 
    "both_from_env": true
  }
}
```

This makes debugging much easier - you can see exactly where credentials are coming from.

## Backward Compatibility

- Existing users with database credentials continue to work
- No database migrations required
- Old sync scripts still work but are no longer needed
- Gradual migration possible (some users from env, others from database)

## Security Notes

- Environment variables are only checked at runtime (not cached)
- Credentials are still encrypted in database using Laravel's encryption
- Logs show masked credentials (first 8 + last 4 characters)
- No credentials are stored in plain text in code

---

**The manual sync script era is over! Just set your `.env` variables and everything works automatically.**