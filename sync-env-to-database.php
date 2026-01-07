<?php

/**
 * Sync Environment Pushover Credentials to Database
 * 
 * This script will update all users' Pushover credentials in the database
 * to match what's configured in the .env file
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SYNC ENV PUSHOVER CREDENTIALS TO DATABASE ===\n";
echo "Environment: " . env('APP_ENV') . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n\n";

// Get credentials from environment
$envUserKey = env('PUSHOVER_USER_KEY');
$envApiToken = env('PUSHOVER_API_TOKEN');

echo "Environment variables:\n";
echo "PUSHOVER_USER_KEY: " . ($envUserKey ? 'SET (' . strlen($envUserKey) . ' chars)' : 'NOT SET') . "\n";
echo "PUSHOVER_API_TOKEN: " . ($envApiToken ? 'SET (' . strlen($envApiToken) . ' chars)' : 'NOT SET') . "\n\n";

if (!$envUserKey || !$envApiToken) {
    echo "❌ Error: PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN must be set in .env file\n";
    exit(1);
}

if (strlen($envUserKey) !== 30) {
    echo "❌ Error: PUSHOVER_USER_KEY should be 30 characters (got " . strlen($envUserKey) . ")\n";
    exit(1);
}

if (strlen($envApiToken) !== 30) {
    echo "❌ Error: PUSHOVER_API_TOKEN should be 30 characters (got " . strlen($envApiToken) . ")\n";
    exit(1);
}

try {
    // Find all users with Pushover enabled
    $pushoverUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)->get();
    
    echo "Found " . $pushoverUsers->count() . " users with Pushover enabled:\n\n";
    
    foreach ($pushoverUsers as $settings) {
        echo "User ID {$settings->user_id}:\n";
        echo "  Current user key: " . substr($settings->pushover_user_key ?? 'NULL', 0, 8) . "..." . substr($settings->pushover_user_key ?? '', -4) . "\n";
        echo "  Current API token: " . substr($settings->pushover_api_token ?? 'NULL', 0, 8) . "..." . substr($settings->pushover_api_token ?? '', -4) . "\n";
        
        // Update with environment credentials
        $settings->pushover_user_key = $envUserKey;
        $settings->pushover_api_token = $envApiToken;
        $settings->save();
        
        echo "  ✅ Updated to match environment variables\n";
        echo "  New user key: " . substr($envUserKey, 0, 8) . "..." . substr($envUserKey, -4) . "\n";
        echo "  New API token: " . substr($envApiToken, 0, 8) . "..." . substr($envApiToken, -4) . "\n\n";
    }
    
    if ($pushoverUsers->count() === 0) {
        echo "No users found with Pushover enabled.\n";
        echo "You may need to enable Pushover for users through the web interface first.\n\n";
        
        // Show all notification settings
        $allSettings = \App\Models\NotificationSettings::all();
        echo "All notification settings:\n";
        foreach ($allSettings as $settings) {
            echo "  User ID {$settings->user_id}: pushover_enabled=" . ($settings->pushover_enabled ? 'true' : 'false') . "\n";
        }
    } else {
        echo "=== TESTING UPDATED CREDENTIALS ===\n";
        
        // Test with the first user
        $testUser = $pushoverUsers->first();
        echo "Testing with User ID {$testUser->user_id}...\n";
        
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->asForm()
            ->post('https://api.pushover.net/1/messages.json', [
                'token' => $envApiToken,
                'user' => $envUserKey,
                'message' => 'Database credentials synced with .env on ' . gethostname() . ' at ' . date('Y-m-d H:i:s'),
                'title' => 'Credentials Sync Test',
                'priority' => 0,
            ]);
        
        echo "Test result: HTTP " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
        
        if ($response->successful()) {
            echo "✅ SUCCESS! Pushover notifications are now working!\n";
        } else {
            echo "❌ FAILED! There may be an issue with the environment credentials.\n";
            
            // Parse response for specific errors
            $responseData = json_decode($response->body(), true);
            if (isset($responseData['errors'])) {
                echo "Specific errors:\n";
                foreach ($responseData['errors'] as $error) {
                    echo "  - $error\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Exception: " . get_class($e) . "\n";
    exit(1);
}

echo "\n=== SYNC COMPLETE ===\n";