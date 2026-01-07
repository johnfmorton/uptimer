<?php

/**
 * Check Pushover Credentials Configuration
 * 
 * This script shows how the new environment-first credential system works.
 * It demonstrates the priority: .env variables take precedence over database values.
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PUSHOVER CREDENTIALS CHECK (Environment-First System) ===\n";
echo "Environment: " . env('APP_ENV') . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n\n";

// Check environment variables
$envUserKey = env('PUSHOVER_USER_KEY');
$envApiToken = env('PUSHOVER_API_TOKEN');

echo "1. ENVIRONMENT VARIABLES (.env file):\n";
echo "   PUSHOVER_USER_KEY: " . ($envUserKey ? 'SET (' . strlen($envUserKey) . ' chars)' : 'NOT SET') . "\n";
echo "   PUSHOVER_API_TOKEN: " . ($envApiToken ? 'SET (' . strlen($envApiToken) . ' chars)' : 'NOT SET') . "\n";

$envValid = $envUserKey && strlen($envUserKey) === 30 && $envApiToken && strlen($envApiToken) === 30;
echo "   Environment credentials valid: " . ($envValid ? '✅ YES' : '❌ NO') . "\n\n";

// Check database settings
echo "2. DATABASE NOTIFICATION SETTINGS:\n";
try {
    $users = \App\Models\User::with('notificationSettings')->get();
    
    if ($users->count() === 0) {
        echo "   No users found in database.\n\n";
    } else {
        foreach ($users as $user) {
            $settings = $user->notificationSettings;
            
            echo "   User #{$user->id} ({$user->email}):\n";
            
            if (!$settings) {
                echo "     - No notification settings configured\n";
                continue;
            }
            
            echo "     - Pushover enabled: " . ($settings->pushover_enabled ? 'YES' : 'NO') . "\n";
            echo "     - Database user key: " . (!empty($settings->pushover_user_key) ? 'SET (' . strlen($settings->pushover_user_key) . ' chars)' : 'NOT SET') . "\n";
            echo "     - Database API token: " . (!empty($settings->pushover_api_token) ? 'SET (' . strlen($settings->pushover_api_token) . ' chars)' : 'NOT SET') . "\n";
            
            // Show effective credentials using new methods
            $effectiveUserKey = $settings->getEffectivePushoverUserKey();
            $effectiveApiToken = $settings->getEffectivePushoverApiToken();
            $credentialSource = $settings->getPushoverCredentialSource();
            $effectivelyEnabled = $settings->isPushoverEffectivelyEnabled();
            
            echo "     - Effective user key: " . ($effectiveUserKey ? 'SET (' . strlen($effectiveUserKey) . ' chars)' : 'NOT SET') . "\n";
            echo "     - Effective API token: " . ($effectiveApiToken ? 'SET (' . strlen($effectiveApiToken) . ' chars)' : 'NOT SET') . "\n";
            echo "     - Credential source: " . strtoupper($credentialSource) . "\n";
            echo "     - Effectively enabled: " . ($effectivelyEnabled ? '✅ YES' : '❌ NO') . "\n";
            
            if ($effectivelyEnabled) {
                echo "     - User key preview: " . substr($effectiveUserKey, 0, 8) . "..." . substr($effectiveUserKey, -4) . "\n";
                echo "     - API token preview: " . substr($effectiveApiToken, 0, 8) . "..." . substr($effectiveApiToken, -4) . "\n";
            }
            
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n\n";
}

// Show how the new system works
echo "3. HOW THE NEW SYSTEM WORKS:\n";
echo "   Priority Order:\n";
echo "   1. Environment variables (.env file) - HIGHEST PRIORITY\n";
echo "   2. Database values (per user) - FALLBACK\n\n";

echo "   Benefits:\n";
echo "   ✅ .env file is now the source of truth\n";
echo "   ✅ No manual sync scripts needed\n";
echo "   ✅ Automatic fallback to database if .env not configured\n";
echo "   ✅ Per-user database credentials still work for multi-tenant setups\n";
echo "   ✅ Easy production deployment - just update .env\n\n";

// Test with a user if available
echo "4. TESTING WITH ACTUAL USER:\n";
try {
    $testUser = \App\Models\User::whereHas('notificationSettings', function($query) {
        $query->where('pushover_enabled', true);
    })->first();
    
    if ($testUser && $testUser->notificationSettings) {
        $settings = $testUser->notificationSettings;
        
        echo "   Testing with User #{$testUser->id}:\n";
        echo "   Credential source: " . strtoupper($settings->getPushoverCredentialSource()) . "\n";
        echo "   Effectively enabled: " . ($settings->isPushoverEffectivelyEnabled() ? 'YES' : 'NO') . "\n";
        
        if ($settings->isPushoverEffectivelyEnabled()) {
            echo "   Sending test notification...\n";
            
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendTestPushover($testUser);
                echo "   ✅ Test notification sent successfully!\n";
                
            } catch (\Exception $e) {
                echo "   ❌ Test notification failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ⚠️  Cannot test - Pushover not effectively enabled\n";
        }
        
    } else {
        echo "   No users found with Pushover enabled\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Testing error: " . $e->getMessage() . "\n";
}

echo "\n";

// Migration guidance
echo "5. MIGRATION GUIDANCE:\n";
if ($envValid) {
    echo "   ✅ Environment credentials are configured and valid\n";
    echo "   ✅ All users will automatically use environment credentials\n";
    echo "   ✅ No manual intervention needed\n";
    echo "   ✅ You can remove database credentials if desired (optional)\n";
} else {
    echo "   ⚠️  Environment credentials not configured\n";
    echo "   📝 To migrate to environment-first system:\n";
    echo "      1. Add PUSHOVER_USER_KEY=your_30_char_key to .env\n";
    echo "      2. Add PUSHOVER_API_TOKEN=your_30_char_token to .env\n";
    echo "      3. Deploy to production\n";
    echo "      4. All users will automatically use new credentials\n";
    echo "      5. Database credentials become backup/fallback\n";
}

echo "\n=== CHECK COMPLETE ===\n";