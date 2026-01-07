<?php

/**
 * Check Production Pushover Credentials
 * 
 * Run this on production to see what credentials are stored
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRODUCTION CREDENTIALS CHECK ===\n";
echo "Environment: " . env('APP_ENV') . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n\n";

try {
    $pushoverUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)
        ->whereNotNull('pushover_user_key')
        ->whereNotNull('pushover_api_token')
        ->get();
        
    echo "Found " . $pushoverUsers->count() . " users with Pushover enabled:\n\n";
    
    foreach ($pushoverUsers as $settings) {
        echo "User ID: {$settings->user_id}\n";
        echo "User Key: " . substr($settings->pushover_user_key, 0, 8) . "..." . substr($settings->pushover_user_key, -4) . "\n";
        echo "API Token: " . substr($settings->pushover_api_token, 0, 8) . "..." . substr($settings->pushover_api_token, -4) . "\n";
        echo "User Key Length: " . strlen($settings->pushover_user_key) . "\n";
        echo "API Token Length: " . strlen($settings->pushover_api_token) . "\n";
        
        // Check if credentials look suspicious
        if (strpos($settings->pushover_api_token, '.net') !== false) {
            echo "⚠️  WARNING: API token contains '.net' - this looks like a URL, not a token!\n";
        }
        
        if (strlen($settings->pushover_user_key) !== 30) {
            echo "⚠️  WARNING: User key should be 30 characters\n";
        }
        
        if (strlen($settings->pushover_api_token) !== 30) {
            echo "⚠️  WARNING: API token should be 30 characters\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}