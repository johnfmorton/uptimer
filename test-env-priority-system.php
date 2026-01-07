<?php

/**
 * Test Environment Priority System for Pushover Credentials
 * 
 * This script demonstrates the new automatic environment variable priority system.
 * No more manual sync scripts needed - .env variables automatically override database values.
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ENVIRONMENT PRIORITY SYSTEM TEST ===\n";
echo "Environment: " . env('APP_ENV') . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n\n";

// Get credentials from environment
$envUserKey = env('PUSHOVER_USER_KEY');
$envApiToken = env('PUSHOVER_API_TOKEN');

echo "ENVIRONMENT VARIABLES (.env file):\n";
echo "PUSHOVER_USER_KEY: " . ($envUserKey ? 'SET (' . strlen($envUserKey) . ' chars)' : 'NOT SET') . "\n";
echo "PUSHOVER_API_TOKEN: " . ($envApiToken ? 'SET (' . strlen($envApiToken) . ' chars)' : 'NOT SET') . "\n\n";

try {
    // Find users with Pushover enabled
    $pushoverUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)->get();
    
    echo "USERS WITH PUSHOVER ENABLED: " . $pushoverUsers->count() . "\n\n";
    
    foreach ($pushoverUsers as $settings) {
        echo "User ID {$settings->user_id}:\n";
        
        // Show database values
        echo "  Database credentials:\n";
        echo "    User Key: " . ($settings->pushover_user_key ? substr($settings->pushover_user_key, 0, 8) . '...' . substr($settings->pushover_user_key, -4) . ' (' . strlen($settings->pushover_user_key) . ' chars)' : 'NOT SET') . "\n";
        echo "    API Token: " . ($settings->pushover_api_token ? substr($settings->pushover_api_token, 0, 8) . '...' . substr($settings->pushover_api_token, -4) . ' (' . strlen($settings->pushover_api_token) . ' chars)' : 'NOT SET') . "\n";
        
        // Show effective values (what the app will actually use)
        $effectiveUserKey = $settings->getEffectivePushoverUserKey();
        $effectiveApiToken = $settings->getEffectivePushoverApiToken();
        
        echo "  Effective credentials (what the app uses):\n";
        echo "    User Key: " . ($effectiveUserKey ? substr($effectiveUserKey, 0, 8) . '...' . substr($effectiveUserKey, -4) . ' (' . strlen($effectiveUserKey) . ' chars)' : 'NOT SET') . "\n";
        echo "    API Token: " . ($effectiveApiToken ? substr($effectiveApiToken, 0, 8) . '...' . substr($effectiveApiToken, -4) . ' (' . strlen($effectiveApiToken) . ' chars)' : 'NOT SET') . "\n";
        
        // Show credential sources
        $sources = $settings->getPushoverCredentialSources();
        echo "  Credential sources:\n";
        echo "    User Key: {$sources['user_key_source']}\n";
        echo "    API Token: {$sources['api_token_source']}\n";
        echo "    Both from .env: " . ($sources['both_from_env'] ? 'YES' : 'NO') . "\n";
        
        // Show if effectively enabled
        echo "  Effectively enabled: " . ($settings->isPushoverEffectivelyEnabled() ? 'YES' : 'NO') . "\n";
        
        echo "\n";
    }
    
    if ($pushoverUsers->count() === 0) {
        echo "No users found with Pushover enabled.\n";
        echo "You may need to enable Pushover for users through the web interface first.\n\n";
    }
    
    echo "=== SYSTEM BEHAVIOR EXPLANATION ===\n";
    echo "The new system works as follows:\n\n";
    
    echo "1. AUTOMATIC PRIORITY:\n";
    echo "   - If PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN are set in .env (and are 30 chars), they are used\n";
    echo "   - Otherwise, database values are used as fallback\n";
    echo "   - No manual sync scripts needed!\n\n";
    
    echo "2. METHODS AVAILABLE:\n";
    echo "   - getEffectivePushoverUserKey(): Returns the user key that will actually be used\n";
    echo "   - getEffectivePushoverApiToken(): Returns the API token that will actually be used\n";
    echo "   - isPushoverEffectivelyEnabled(): Checks if Pushover is enabled AND has valid credentials\n";
    echo "   - getPushoverCredentialSources(): Shows where credentials are coming from\n\n";
    
    echo "3. BENEFITS:\n";
    echo "   - Set credentials in .env once, they work for all users\n";
    echo "   - No need to run sync scripts after .env updates\n";
    echo "   - Automatic fallback to database if .env not configured\n";
    echo "   - Clear logging shows credential sources for debugging\n\n";
    
    if ($envUserKey && $envApiToken) {
        echo "✅ READY TO TEST:\n";
        echo "Your .env has Pushover credentials configured.\n";
        echo "All users with Pushover enabled will now use these credentials automatically.\n\n";
        
        // Test with the first user if available
        $testUser = $pushoverUsers->first();
        if ($testUser) {
            echo "Testing with User ID {$testUser->user_id}...\n";
            
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendTestPushover($testUser->user);
                echo "✅ Test notification sent successfully!\n";
                echo "Check your Pushover app for the test message.\n";
            } catch (Exception $e) {
                echo "❌ Test failed: " . $e->getMessage() . "\n";
                echo "Check the logs for more details: ddev artisan pail\n";
            }
        }
    } else {
        echo "⚠️  SETUP NEEDED:\n";
        echo "To use the environment priority system:\n";
        echo "1. Add PUSHOVER_USER_KEY=your_30_char_user_key to .env\n";
        echo "2. Add PUSHOVER_API_TOKEN=your_30_char_api_token to .env\n";
        echo "3. Enable Pushover for users through the web interface\n";
        echo "4. Credentials will be used automatically - no sync needed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Exception: " . get_class($e) . "\n";
}

echo "\n=== TEST COMPLETE ===\n";