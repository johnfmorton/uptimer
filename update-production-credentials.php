<?php

/**
 * Update Production Pushover Credentials
 * 
 * Run this on production to update invalid credentials with working ones
 * Usage: php update-production-credentials.php [user_id] [new_user_key] [new_api_token]
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== UPDATE PRODUCTION PUSHOVER CREDENTIALS ===\n";
echo "Environment: " . env('APP_ENV') . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n\n";

// Get command line arguments
$userId = $argv[1] ?? null;
$newUserKey = $argv[2] ?? null;
$newApiToken = $argv[3] ?? null;

if (!$userId || !$newUserKey || !$newApiToken) {
    echo "Usage: php update-production-credentials.php [user_id] [new_user_key] [new_api_token]\n\n";
    echo "Example: php update-production-credentials.php 1 uesjb1upabcdef123456789012345678 aznwancfabcdef123456789012345678\n\n";
    
    // Show current credentials
    echo "Current Pushover users:\n";
    $pushoverUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)->get();
    foreach ($pushoverUsers as $settings) {
        echo "  User ID {$settings->user_id}: ";
        echo "user_key=" . substr($settings->pushover_user_key ?? 'NULL', 0, 8) . "..., ";
        echo "api_token=" . substr($settings->pushover_api_token ?? 'NULL', 0, 8) . "...\n";
    }
    exit(1);
}

// Validate input
if (strlen($newUserKey) !== 30) {
    echo "❌ Error: User key must be exactly 30 characters (got " . strlen($newUserKey) . ")\n";
    exit(1);
}

if (strlen($newApiToken) !== 30) {
    echo "❌ Error: API token must be exactly 30 characters (got " . strlen($newApiToken) . ")\n";
    exit(1);
}

try {
    // Find the user's notification settings
    $settings = \App\Models\NotificationSettings::where('user_id', $userId)->first();
    
    if (!$settings) {
        echo "❌ Error: No notification settings found for user ID $userId\n";
        exit(1);
    }
    
    echo "Found notification settings for user ID $userId\n";
    echo "Current user key: " . substr($settings->pushover_user_key ?? 'NULL', 0, 8) . "...\n";
    echo "Current API token: " . substr($settings->pushover_api_token ?? 'NULL', 0, 8) . "...\n\n";
    
    // Update the credentials
    $settings->pushover_user_key = $newUserKey;
    $settings->pushover_api_token = $newApiToken;
    $settings->pushover_enabled = true;
    $settings->save();
    
    echo "✅ Updated credentials for user ID $userId\n";
    echo "New user key: " . substr($newUserKey, 0, 8) . "..." . substr($newUserKey, -4) . "\n";
    echo "New API token: " . substr($newApiToken, 0, 8) . "..." . substr($newApiToken, -4) . "\n\n";
    
    // Test the new credentials
    echo "Testing new credentials...\n";
    
    $response = \Illuminate\Support\Facades\Http::timeout(30)
        ->asForm()
        ->post('https://api.pushover.net/1/messages.json', [
            'token' => $newApiToken,
            'user' => $newUserKey,
            'message' => 'Credentials updated successfully on ' . gethostname() . ' at ' . date('Y-m-d H:i:s'),
            'title' => 'Production Credentials Updated',
            'priority' => 0,
        ]);
    
    echo "Test result: HTTP " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n";
    
    if ($response->successful()) {
        echo "✅ Credentials test PASSED - Pushover is now working!\n";
    } else {
        echo "❌ Credentials test FAILED - Check the credentials and try again\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}