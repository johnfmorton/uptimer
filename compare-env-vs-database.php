<?php

/**
 * Compare Environment vs Database Pushover Credentials
 * 
 * Shows the difference between .env and database credentials
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ENVIRONMENT vs DATABASE CREDENTIALS COMPARISON ===\n";
echo "Environment: " . env('APP_ENV') . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n\n";

// Get credentials from environment
$envUserKey = env('PUSHOVER_USER_KEY');
$envApiToken = env('PUSHOVER_API_TOKEN');

echo "ENVIRONMENT VARIABLES (.env file):\n";
echo "PUSHOVER_USER_KEY: " . ($envUserKey ? substr($envUserKey, 0, 8) . '...' . substr($envUserKey, -4) . ' (' . strlen($envUserKey) . ' chars)' : 'NOT SET') . "\n";
echo "PUSHOVER_API_TOKEN: " . ($envApiToken ? substr($envApiToken, 0, 8) . '...' . substr($envApiToken, -4) . ' (' . strlen($envApiToken) . ' chars)' : 'NOT SET') . "\n\n";

try {
    // Get credentials from database
    $pushoverUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)
        ->whereNotNull('pushover_user_key')
        ->whereNotNull('pushover_api_token')
        ->get();
    
    echo "DATABASE CREDENTIALS (notification_settings table):\n";
    
    if ($pushoverUsers->count() === 0) {
        echo "No users found with complete Pushover configuration in database.\n\n";
        
        // Show users with partial config
        $partialUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)->get();
        if ($partialUsers->count() > 0) {
            echo "Users with Pushover enabled but incomplete config:\n";
            foreach ($partialUsers as $settings) {
                echo "  User ID {$settings->user_id}: ";
                echo "user_key=" . (!empty($settings->pushover_user_key) ? 'SET' : 'MISSING') . ", ";
                echo "api_token=" . (!empty($settings->pushover_api_token) ? 'SET' : 'MISSING') . "\n";
            }
        }
    } else {
        foreach ($pushoverUsers as $settings) {
            echo "User ID {$settings->user_id}:\n";
            echo "  User Key: " . substr($settings->pushover_user_key, 0, 8) . '...' . substr($settings->pushover_user_key, -4) . ' (' . strlen($settings->pushover_user_key) . ' chars)' . "\n";
            echo "  API Token: " . substr($settings->pushover_api_token, 0, 8) . '...' . substr($settings->pushover_api_token, -4) . ' (' . strlen($settings->pushover_api_token) . ' chars)' . "\n";
            
            // Compare with environment
            $userKeyMatch = $settings->pushover_user_key === $envUserKey;
            $apiTokenMatch = $settings->pushover_api_token === $envApiToken;
            
            echo "  Matches .env user key: " . ($userKeyMatch ? '✅ YES' : '❌ NO') . "\n";
            echo "  Matches .env API token: " . ($apiTokenMatch ? '✅ YES' : '❌ NO') . "\n";
            
            if (!$userKeyMatch || !$apiTokenMatch) {
                echo "  ⚠️  DATABASE CREDENTIALS DO NOT MATCH ENVIRONMENT!\n";
            }
            echo "\n";
        }
    }
    
    echo "=== DIAGNOSIS ===\n";
    if ($envUserKey && $envApiToken) {
        echo "✅ Environment variables are properly configured\n";
    } else {
        echo "❌ Environment variables are missing\n";
    }
    
    if ($pushoverUsers->count() > 0) {
        $allMatch = true;
        foreach ($pushoverUsers as $settings) {
            if ($settings->pushover_user_key !== $envUserKey || $settings->pushover_api_token !== $envApiToken) {
                $allMatch = false;
                break;
            }
        }
        
        if ($allMatch) {
            echo "✅ Database credentials match environment variables\n";
        } else {
            echo "❌ Database credentials DO NOT match environment variables\n";
            echo "   This is why Pushover notifications are failing in production!\n";
            echo "   The app uses database credentials, not environment variables.\n";
        }
    } else {
        echo "❌ No complete Pushover configurations found in database\n";
    }
    
    echo "\nRECOMMENDED ACTION:\n";
    if ($envUserKey && $envApiToken && $pushoverUsers->count() > 0) {
        echo "Run: php sync-env-to-database.php\n";
        echo "This will update database credentials to match your working .env file.\n";
    } else {
        echo "1. Ensure PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN are set in .env\n";
        echo "2. Enable Pushover for users through the web interface\n";
        echo "3. Run sync-env-to-database.php to sync credentials\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}